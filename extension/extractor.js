/**
 * VDownload Pro - Standalone Video Extraction Engine
 * Copyright (c) 2026 cs.baguosps@gmail.com
 * All rights reserved.
 * 
 * Developed by: cs.baguosps@gmail.com
 * 100% Client-Side Extraction for TikTok, Shopee Video, and Instagram
 */

(function (root) {
  const Extractor = {};

  Extractor.detectPlatform = function (url) {
    if (!url) return null;
    const u = url.toLowerCase();
    if (u.includes('youtube.com') || u.includes('youtu.be')) {
      return 'youtube';
    }
    if (u.includes('tiktok.com') || u.includes('douyin.com') || u.includes('vt.tiktok')) {
      return 'tiktok';
    }
    if (u.includes('instagram.com') || u.includes('instagr.am')) {
      return 'instagram';
    }
    if (u.includes('shopee.co.id') || u.includes('shopee.com') || u.includes('shp.ee') || u.includes('sv.shopee')) {
      return 'shopee';
    }
    return null;
  };

  /**
   * TikTok Video Extractor (TikWM Standalone API)
   */
  Extractor.extractTikTok = async function (url) {
    const apiUrl = `https://www.tikwm.com/api/?url=${encodeURIComponent(url)}`;
    const resp = await fetch(apiUrl);
    if (!resp.ok) {
      throw new Error('Gagal menghubungi server ekstraksi TikTok.');
    }

    const data = await resp.json();
    if (data.code !== 0 || !data.data) {
      throw new Error(data.msg || 'Gagal mengekstrak video TikTok. Pastikan video publik.');
    }

    const d = data.data;
    const author = d.author?.unique_id || d.author?.nickname || 'tiktok_user';
    const cleanId = d.id || String(Date.now());
    const downloads = [];

    // 1. True HD (No Watermark)
    if (d.hdplay) {
      const hdUrl = d.hdplay.startsWith('http') ? d.hdplay : `https://www.tikwm.com${d.hdplay}`;
      downloads.push({
        type: 'video',
        label: 'Unduh True HD (Tanpa Watermark)',
        quality: '⭐ True HD',
        icon: 'badge-hd',
        btnClass: 'btn-tiktok-hd',
        url: hdUrl,
        filename: `tiktok_${author}_${cleanId}_TrueHD.mp4`
      });
    }

    // 2. Standard Clean Video
    if (d.play) {
      const playUrl = d.play.startsWith('http') ? d.play : `https://www.tikwm.com${d.play}`;
      downloads.push({
        type: 'video',
        label: 'Unduh Standar (Tanpa Watermark)',
        quality: 'Clean SD',
        icon: 'play',
        btnClass: 'btn-tiktok',
        url: playUrl,
        filename: `tiktok_${author}_${cleanId}_SD.mp4`
      });
    }

    // 3. Audio MP3
    if (d.music) {
      const musicUrl = d.music.startsWith('http') ? d.music : `https://www.tikwm.com${d.music}`;
      const songTitle = d.music_info?.title ? ` (${d.music_info.title})` : '';
      downloads.push({
        type: 'audio',
        label: `Unduh Musik MP3${songTitle}`,
        quality: 'Audio MP3',
        icon: 'music',
        btnClass: 'btn-audio',
        url: musicUrl,
        filename: `tiktok_${author}_${cleanId}_audio.mp3`
      });
    }

    // 4. Cover Thumbnail
    if (d.cover) {
      downloads.push({
        type: 'image',
        label: 'Unduh Cover / Thumbnail HD',
        quality: 'HD Image',
        icon: 'image',
        btnClass: 'btn-cover',
        url: d.cover,
        filename: `tiktok_${author}_${cleanId}_cover.jpg`
      });
    }

    return {
      platform: 'tiktok',
      platformName: 'TikTok',
      title: d.title || 'TikTok Video',
      author: author,
      authorAvatar: d.author?.avatar || '',
      duration: d.duration ? `${d.duration}s` : '00:00',
      thumbnail: d.cover || '',
      previewVideo: d.play || d.hdplay || '',
      downloads: downloads
    };
  };

  /**
   * Shopee Video Extractor (Direct VOD & Master Stream Resolver)
   */
  Extractor.extractShopee = async function (url) {
    const resp = await fetch(url, {
      headers: {
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
      }
    });

    if (!resp.ok) {
      throw new Error(`Gagal membuka halaman Shopee (HTTP ${resp.status}).`);
    }

    const html = await resp.text();
    let videoUrl = '';
    let caption = 'Shopee Video';
    let coverUrl = '';
    let author = 'Shopee User';
    let avatarUrl = '';

    // Strategy 1: __NEXT_DATA__ JSON
    const nextMatch = html.match(/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s);
    if (nextMatch) {
      try {
        const nextJson = JSON.parse(nextMatch[1]);
        const media = nextJson?.props?.pageProps?.mediaInfo;
        if (media && media.video) {
          videoUrl = media.video.watermarkVideoUrl || media.video.videoUrl || '';
          caption = media.video.caption || caption;
          coverUrl = media.video.watermarkCoverUrl || media.video.coverUrl || '';
          author = media.userInfo?.videoUserName || author;
          const avatarKey = media.userInfo?.videoUserAvatar || '';
          if (avatarKey) {
            avatarUrl = `https://down-aka-id.vod.susercontent.com/${avatarKey}`;
          }
        }
      } catch (e) {
        // fallback
      }
    }

    // Strategy 2: Direct Regex Search in HTML
    if (!videoUrl) {
      const vMatch = html.match(/"watermarkVideoUrl"\s*:\s*"([^"]+)"/) || html.match(/"videoUrl"\s*:\s*"([^"]+)"/);
      if (vMatch) {
        videoUrl = vMatch[1].replace(/\\/g, '');
      }
      const cMatch = html.match(/"caption"\s*:\s*"([^"]+)"/);
      if (cMatch) caption = cMatch[1].replace(/\\/g, '');
      const covMatch = html.match(/"watermarkCoverUrl"\s*:\s*"([^"]+)"/) || html.match(/"coverUrl"\s*:\s*"([^"]+)"/);
      if (covMatch) coverUrl = covMatch[1].replace(/\\/g, '');
    }

    if (!videoUrl) {
      throw new Error('Video Shopee tidak ditemukan dalam halaman tersebut.');
    }

    const cleanId = Math.random().toString(36).substring(2, 10);
    const downloads = [];

    // Resolve Master HD (Raw stream without watermark)
    // Pattern: https://down-aka-id.vod.susercontent.com/api/v4/11110124/mms/id-11110124-6vgyf-mu1gvlscu6f73f.1600...mp4
    const vMatch = videoUrl.match(/(https?:\/\/[^\/]+\/api\/v4\/\d+\/mms\/)(id-[^\.]+)/i);
    let masterUrl = '';
    let defaultUrl = '';

    if (vMatch) {
      const base = vMatch[1];
      const assetId = vMatch[2];
      masterUrl = `${base}${assetId}.mp4`;
      defaultUrl = `${base}${assetId}.default.mp4`;
    }

    // 1. True Master HD (No Watermark)
    if (masterUrl) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video Full HD Asli - Tanpa Watermark',
        quality: '⭐ True Master HD',
        icon: 'badge-hd',
        btnClass: 'btn-shopee',
        url: masterUrl,
        filename: `shopee_${author}_${cleanId}_MasterHD_nowm.mp4`
      });
    }

    // 2. Web SD Compressed
    if (defaultUrl) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video Format Hemat Kuota - Tanpa Watermark',
        quality: 'Web SD',
        icon: 'lightning',
        btnClass: 'btn-sd',
        url: defaultUrl,
        filename: `shopee_${author}_${cleanId}_SD_nowm.mp4`
      });
    }

    // 3. Watermarked Original
    downloads.push({
      type: 'video',
      label: 'Unduh Video Versi Aplikasi (Dengan Watermark)',
      quality: 'With Watermark',
      icon: 'play',
      btnClass: 'btn-wm',
      url: videoUrl,
      filename: `shopee_${author}_${cleanId}_wm.mp4`
    });

    // 4. Cover Image
    if (coverUrl) {
      downloads.push({
        type: 'image',
        label: 'Unduh Cover / Thumbnail HD',
        quality: 'HD JPG',
        icon: 'image',
        btnClass: 'btn-cover',
        url: coverUrl,
        filename: `shopee_cover_${cleanId}.jpg`
      });
    }

    return {
      platform: 'shopee',
      platformName: 'Shopee Video',
      title: caption,
      author: author,
      authorAvatar: avatarUrl,
      duration: '01:00',
      thumbnail: coverUrl,
      previewVideo: masterUrl || defaultUrl || videoUrl,
      downloads: downloads
    };
  };

  /**
   * Instagram Reels Extractor (Standalone with Session ID & GraphQL)
   */
  Extractor.extractInstagram = async function (url) {
    const sMatch = url.match(/(?:reel|reels|p|tv)\/([A-Za-z0-9_-]+)/);
    if (!sMatch) {
      throw new Error('URL Instagram tidak valid. Contoh: https://www.instagram.com/reel/C8h0YdKP2fC/');
    }
    const shortcode = sMatch[1];

    // GraphQL Doc IDs for Instagram Reels/Post media
    const docIds = ['8845758582119845', '10015901848480474'];
    let mediaData = null;

    for (const docId of docIds) {
      try {
        const body = new URLSearchParams();
        body.append('doc_id', docId);
        body.append('variables', JSON.stringify({ shortcode: shortcode }));

        const headers = {
          'X-IG-App-ID': '936619743392459',
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json'
        };

        const res = await fetch('https://www.instagram.com/api/graphql', {
          method: 'POST',
          headers: headers,
          body: body.toString(),
          credentials: 'include'
        });

        if (res.ok) {
          const json = await res.json();
          if (json?.data?.xdt_shortcode_media) {
            mediaData = json.data.xdt_shortcode_media;
            break;
          }
        }
      } catch (err) {
        // try next
      }
    }

    if (!mediaData) {
      throw new Error('Tidak dapat mengekstrak video Instagram secara langsung. Pastikan Anda sudah login ke instagram.com di browser ini.');
    }

    const videoUrl = mediaData.video_url || '';
    const displayUrl = mediaData.display_url || '';
    const author = mediaData.owner?.username || 'instagram_user';
    const caption = mediaData.edge_media_to_caption?.edges?.[0]?.node?.text || 'Instagram Reel';
    const downloads = [];

    if (videoUrl) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video Instagram - Kualitas HD',
        quality: '⭐ True HD',
        icon: 'badge-hd',
        btnClass: 'btn-instagram',
        url: videoUrl,
        filename: `instagram_${author}_${shortcode}_HD.mp4`
      });
    }

    if (displayUrl) {
      downloads.push({
        type: 'image',
        label: 'Unduh Thumbnail HD (Resolusi Penuh)',
        quality: 'HD Image',
        icon: 'image',
        btnClass: 'btn-cover',
        url: displayUrl,
        filename: `instagram_${author}_${shortcode}_thumb.jpg`
      });
    }

    return {
      platform: 'instagram',
      platformName: 'Instagram',
      title: caption,
      author: author,
      authorAvatar: mediaData.owner?.profile_pic_url || '',
      duration: mediaData.video_duration ? `${Math.round(mediaData.video_duration)}s` : '',
      thumbnail: displayUrl,
      previewVideo: videoUrl,
      downloads: downloads
    };
  };

  /**
   * YouTube Video & Music Extractor
   */
  Extractor.extractYouTube = async function (url) {
    const vMatch = url.match(/(?:v=|youtu\.be\/|shorts\/|embed\/)([A-Za-z0-9_-]{11})/);
    const videoId = vMatch ? vMatch[1] : '';

    // Strategy 1: Call VDownload Pro backend if reachable
    let serverBaseUrl = 'http://localhost/vdownload';
    if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.sync) {
      try {
        const stored = await chrome.storage.sync.get(['serverBaseUrl']);
        if (stored && stored.serverBaseUrl) {
          serverBaseUrl = stored.serverBaseUrl.replace(/\/+$/, '');
        }
      } catch (e) {
        // use default
      }
    }

    try {
      const resp = await fetch(`${serverBaseUrl}/download/fetch`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url })
      });

      if (resp.ok) {
        const json = await resp.json();
        if (json && json.success && json.data) {
          return json.data;
        }
      }
    } catch (e) {
      // Backend not running, proceed to fallback oEmbed
    }

    // Strategy 2: Client-side YouTube oEmbed
    if (videoId) {
      const oembedUrl = `https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`;
      const oembedResp = await fetch(oembedUrl);
      if (oembedResp.ok) {
        const oembed = await oembedResp.json();
        const maxThumb = `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
        return {
          platform: 'youtube',
          platformName: 'YouTube',
          title: oembed.title || 'YouTube Video',
          author: oembed.author_name || 'YouTube Creator',
          authorAvatar: 'https://www.youtube.com/favicon.ico',
          duration: 'Video',
          thumbnail: maxThumb,
          previewVideo: '',
          downloads: [
            {
              type: 'video',
              label: 'Unduh Video via VDownload Web Server',
              quality: '⭐ Full HD',
              icon: 'badge-hd',
              btnClass: 'btn-youtube-hd',
              url: `${serverBaseUrl}/?url=${encodeURIComponent(url)}`,
              filename: `youtube_${videoId}.mp4`
            },
            {
              type: 'image',
              label: 'Unduh Cover / Thumbnail HD',
              quality: 'HD Image',
              icon: 'image',
              btnClass: 'btn-cover',
              url: maxThumb,
              filename: `youtube_${videoId}_cover.jpg`
            }
          ]
        };
      }
    }

    throw new Error('Gagal mengekstrak video YouTube. Pastikan URL valid dan server VDownload aktif.');
  };

  /**
   * Unified Main Extraction Function
   * Tries local server engine first for maximum fidelity & true HD metadata;
   * gracefully falls back to client-side standalone engine if server is offline.
   */
  Extractor.extractMedia = async function (url) {
    const platform = Extractor.detectPlatform(url);
    if (!platform) {
      throw new Error('Format tautan tidak dikenali. Masukkan tautan YouTube, TikTok, Shopee, atau Instagram.');
    }

    // Try server engine first if available
    let serverBaseUrl = 'http://localhost/vdownload';
    if (typeof chrome !== 'undefined' && chrome.storage && chrome.storage.sync) {
      try {
        const stored = await chrome.storage.sync.get(['serverBaseUrl']);
        if (stored && stored.serverBaseUrl) {
          serverBaseUrl = stored.serverBaseUrl.replace(/\/+$/, '');
        }
      } catch (e) {}
    }

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 3500);
      const resp = await fetch(`${serverBaseUrl}/download/fetch`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url }),
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      if (resp.ok) {
        const json = await resp.json();
        if (json && json.success && json.data) {
          return json.data;
        }
      }
    } catch (e) {
      // Server not reachable or timeout, proceed to standalone client-side engine
    }

    if (platform === 'youtube') {
      return await Extractor.extractYouTube(url);
    } else if (platform === 'tiktok') {
      return await Extractor.extractTikTok(url);
    } else if (platform === 'shopee') {
      return await Extractor.extractShopee(url);
    } else if (platform === 'instagram') {
      return await Extractor.extractInstagram(url);
    }

    throw new Error('Platform belum didukung.');
  };

  root.VDownloadExtractor = Extractor;
})(typeof self !== 'undefined' ? self : (typeof window !== 'undefined' ? window : this));
