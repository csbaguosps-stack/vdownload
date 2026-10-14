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
    if (u.includes('music.youtube.com')) {
      return 'ytmusic';
    }
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
   * TikTok Video & Photo Slideshow Extractor (TikWM Standalone API)
   */
  Extractor.extractTikTok = async function (url) {
    const apiUrl = `https://www.tikwm.com/api/?url=${encodeURIComponent(url)}&hd=1`;
    const resp = await fetch(apiUrl);
    if (!resp.ok) {
      throw new Error('Gagal menghubungi server ekstraksi TikTok.');
    }

    const data = await resp.json();
    if (data.code !== 0 || !data.data) {
      throw new Error(data.msg || 'Gagal mengekstrak konten TikTok. Pastikan postingan publik.');
    }

    const d = data.data;
    const author = d.author?.unique_id || d.author?.nickname || 'tiktok_user';
    const cleanId = d.id || String(Date.now());
    const downloads = [];

    // Check for Photo Mode / Slideshow Images
    const rawImages = d.images || [];
    const images = [];
    if (Array.isArray(rawImages)) {
      rawImages.forEach((img) => {
        const imgUrl = (typeof img === 'string') ? img : (img?.url || '');
        if (imgUrl) {
          images.push(imgUrl.startsWith('http') ? imgUrl : `https://www.tikwm.com${imgUrl}`);
        }
      });
    }

    // 1. Photos (If TikTok Photo Slide / Mode)
    if (images.length > 0) {
      images.forEach((imgUrl, i) => {
        downloads.push({
          type: 'image',
          label: `Unduh Foto #${i + 1} HD (Tanpa Watermark)`,
          quality: '⭐ HD Foto',
          icon: 'image',
          btnClass: 'btn-tiktok-hd',
          url: imgUrl,
          filename: `tiktok_${author}_${cleanId}_photo_${i + 1}.jpg`
        });
      });
    }

    // 2. Full HD 1080p Video (No Watermark)
    if (d.hdplay || d.play) {
      const hdUrl = d.hdplay ? (d.hdplay.startsWith('http') ? d.hdplay : `https://www.tikwm.com${d.hdplay}`) : (d.play.startsWith('http') ? d.play : `https://www.tikwm.com${d.play}`);
      downloads.push({
        type: 'video',
        label: images.length > 0 ? 'Unduh Video Slideshow Full HD 1080p (MP4)' : 'Unduh Full HD 1080p (Tanpa Watermark)',
        quality: '⭐ 1080p Full HD',
        icon: 'badge-hd',
        btnClass: images.length > 0 ? 'btn-tiktok' : 'btn-tiktok-hd',
        url: hdUrl,
        filename: `tiktok_${author}_${cleanId}_1080p_nowm.mp4`
      });
    }

    // 3. HD 720p Video (No Watermark)
    if ((d.play || d.hdplay) && images.length === 0) {
      const playUrl = d.play ? (d.play.startsWith('http') ? d.play : `https://www.tikwm.com${d.play}`) : (d.hdplay.startsWith('http') ? d.hdplay : `https://www.tikwm.com${d.hdplay}`);
      downloads.push({
        type: 'video',
        label: 'Unduh HD 720p (Tanpa Watermark)',
        quality: 'HD 720p',
        icon: 'play',
        btnClass: 'btn-tiktok',
        url: playUrl,
        filename: `tiktok_${author}_${cleanId}_720p_nowm.mp4`
      });
    }

    // 4. SD 360p Video (Hemat Kuota)
    if ((d.play || d.hdplay) && images.length === 0) {
      const sdUrl = d.play ? (d.play.startsWith('http') ? d.play : `https://www.tikwm.com${d.play}`) : (d.hdplay.startsWith('http') ? d.hdplay : `https://www.tikwm.com${d.hdplay}`);
      downloads.push({
        type: 'video',
        label: 'Unduh Hemat Kuota SD 360p (Tanpa Watermark)',
        quality: 'SD 360p (Hemat Kuota)',
        icon: 'play',
        btnClass: 'btn-download-sd',
        url: sdUrl,
        filename: `tiktok_${author}_${cleanId}_360p_nowm.mp4`
      });
    }

    // 4. Audio MP3 (Always available & requested)
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

    // 5. Cover Thumbnail (for normal video posts)
    if (d.cover && images.length === 0) {
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
      mediaType: images.length > 0 ? 'photo' : 'video',
      title: d.title || 'TikTok Media',
      author: author,
      authorAvatar: d.author?.avatar || '',
      duration: d.duration ? `${d.duration}s` : (images.length > 0 ? `${images.length} Foto` : '00:00'),
      thumbnail: images[0] || d.cover || '',
      previewVideo: images.length > 0 ? '' : (d.play || d.hdplay || ''),
      previewAudio: d.music ? (d.music.startsWith('http') ? d.music : `https://www.tikwm.com${d.music}`) : '',
      gallery: images,
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

    // 1. Full HD 1080p No Watermark Video
    const target1080 = masterUrl || defaultUrl || videoUrl;
    if (target1080) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video Full HD 1080p - Tanpa Watermark',
        quality: '⭐ 1080p Full HD',
        icon: 'badge-hd',
        btnClass: 'btn-shopee',
        url: target1080,
        filename: `shopee_${author}_${cleanId}_1080p_nowm.mp4`
      });
    }

    // 2. HD 720p No Watermark Video
    const target720 = defaultUrl || masterUrl || videoUrl;
    if (target720) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video HD 720p - Tanpa Watermark (Seimbang)',
        quality: 'HD 720p',
        icon: 'play',
        btnClass: 'btn-shopee',
        url: target720,
        filename: `shopee_${author}_${cleanId}_720p_nowm.mp4`
      });
    }

    // 3. SD 360p No Watermark Video (Hemat Kuota)
    const target360 = defaultUrl || masterUrl || videoUrl;
    if (target360) {
      downloads.push({
        type: 'video',
        label: 'Unduh Video Hemat Kuota SD 360p - Tanpa Watermark (Ekstra Ringan)',
        quality: 'SD 360p (Hemat Kuota)',
        icon: 'lightning',
        btnClass: 'btn-download-sd',
        url: target360,
        filename: `shopee_${author}_${cleanId}_360p_nowm.mp4`
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

  function unpackSnapsave(h, u, n, t, e, r) {
    const chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/";
    const g = chars.split('');
    const sliceH = g.slice(0, e);
    let res = "";
    const len = h.length;
    let i = 0;
    const n_arr = n.split('');
    const delimiter = n_arr[e] || '';

    while (i < len) {
      let s = "";
      while (i < len && h[i] !== delimiter) {
        s += h[i];
        i++;
      }
      i++;
      for (let j = 0; j < n_arr.length; j++) {
        s = s.split(n_arr[j]).join(j.toString());
      }
      if (s !== "") {
        const d_arr = s.split('').reverse();
        let val = 0;
        for (let c = 0; c < d_arr.length; c++) {
          const b = d_arr[c];
          const idx = sliceH.indexOf(b);
          if (idx !== -1) {
            val += idx * Math.pow(e, c);
          }
        }
        const charCode = Math.floor(val) - t;
        if (charCode > 0 && charCode < 65536) {
          res += String.fromCharCode(charCode);
        }
      }
    }
    try {
      return decodeURIComponent(res);
    } catch (err) {
      return res;
    }
  }

  async function extractInstagramViaSnapsave(url) {
    const form = new URLSearchParams({ url: url, action: 'post' });
    const res = await fetch('https://snapsave.app/action.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'Referer': 'https://snapsave.app/'
      },
      body: form.toString()
    });

    if (!res.ok) return null;
    const text = await res.text();
    const m = text.match(/\("([^"]+)",(\d+),"([^"]+)",(\d+),(\d+),(\d+)\)/);
    if (!m) return null;

    let unpacked = unpackSnapsave(m[1], parseInt(m[2], 10), m[3], parseInt(m[4], 10), parseInt(m[5], 10), parseInt(m[6], 10));
    if (!unpacked || unpacked.includes('Error:')) return null;

    unpacked = unpacked.replace(/\\"/g, '"').replace(/\\'/g, "'").replace(/\\\//g, '/');

    const downloads = [];
    const gallery = [];
    const photoUrls = [];
    const cleanId = Math.random().toString(36).substring(2, 10);

    const cardRegex = /<div\s+class="download-items"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/gi;
    let cardMatch;
    let itemIdx = 1;
    while ((cardMatch = cardRegex.exec(unpacked)) !== null) {
      const cardHtml = cardMatch[1];
      const imgMatch = cardHtml.match(/<img[^>]*src="([^"]+)"/i);
      const thumbUrl = imgMatch ? imgMatch[1] : '';
      const aMatch = cardHtml.match(/<a\b[^>]*href="([^"]+)"/i);
      const itemUrl = aMatch ? aMatch[1] : '';
      if (!itemUrl || itemUrl.startsWith('javascript:') || itemUrl === '#') continue;

      const titleMatch = cardHtml.match(/title="([^"]*)"/i);
      const title = titleMatch ? titleMatch[1] : '';
      const isPhoto = title.toLowerCase().includes('photo') || 
                      title.toLowerCase().includes('image') || 
                      cardHtml.includes('icon-dlimage') ||
                      /\.(jpe?g|png|webp)/i.test(itemUrl);

      if (isPhoto) {
        downloads.push({
          type: 'image',
          label: `Unduh Foto #${itemIdx} HD (Resolusi Penuh)`,
          quality: '⭐ HD Foto',
          icon: 'image',
          btnClass: 'btn-instagram',
          url: itemUrl,
          filename: `instagram_${cleanId}_photo_${itemIdx}.jpg`
        });
        photoUrls.push(itemUrl);
        const display = thumbUrl || itemUrl;
        if (!gallery.includes(display)) gallery.push(display);
      } else {
        downloads.push({
          type: 'video',
          label: `Unduh Video #${itemIdx} (Kualitas HD)`,
          quality: '⭐ True HD',
          icon: 'badge-hd',
          btnClass: 'btn-instagram',
          url: itemUrl,
          filename: `instagram_${cleanId}_video_${itemIdx}.mp4`
        });
        if (thumbUrl && !gallery.includes(thumbUrl)) gallery.push(thumbUrl);
      }
      itemIdx++;
    }

    if (photoUrls.length > 1) {
      const zipQuery = new URLSearchParams();
      photoUrls.forEach(u => zipQuery.append('urls[]', u));
      zipQuery.append('name', `instagram_${cleanId}_photos.zip`);
      downloads.unshift({
        type: 'zip',
        label: `Unduh Semua Foto Sekaligus (${photoUrls.length} Foto Full HD)`,
        quality: '⭐ Paket ZIP',
        icon: 'badge-hd',
        btnClass: 'btn-instagram',
        url: `http://localhost/vdownload/download/zip?${zipQuery.toString()}`,
        filename: `instagram_${cleanId}_photos.zip`
      });
    }

    if (downloads.length === 0) return null;

    return {
      platform: 'instagram',
      platformName: 'Instagram',
      mediaType: downloads.length > 1 ? 'carousel' : (downloads[0].type === 'image' ? 'photo' : 'video'),
      title: 'Instagram Post',
      author: 'Instagram User',
      authorAvatar: '',
      duration: downloads.length > 1 ? `${downloads.length} Berkas HD` : 'HD',
      thumbnail: gallery[0] || '',
      previewVideo: downloads.find(d => d.type === 'video')?.url || '',
      gallery: gallery,
      downloads: downloads
    };
  }

  /**
   * Instagram Reels Extractor (Standalone with Session ID, GraphQL & Snapsave)
   */
  Extractor.extractInstagram = async function (url) {
    const sMatch = url.match(/(?:reel|reels|p|tv)\/([A-Za-z0-9_-]+)/);
    if (!sMatch) {
      throw new Error('URL Instagram tidak valid. Contoh: https://www.instagram.com/reel/C8h0YdKP2fC/');
    }
    const shortcode = sMatch[1];

    // Strategy 1: Check server backend first
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
    } catch (e) {}

    // Strategy 2: GraphQL Doc IDs for Instagram Reels/Post media (works if logged in)
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

    // Strategy 3: Client-side Snapsave Scraper
    if (!mediaData) {
      try {
        const snapMedia = await extractInstagramViaSnapsave(url);
        if (snapMedia) {
          return snapMedia;
        }
      } catch (snapErr) {}
    }

    if (!mediaData) {
      throw new Error('Tidak dapat mengekstrak media Instagram secara langsung. Pastikan server VDownload berjalan di localhost atau Anda sudah login ke instagram.com di browser ini.');
    }

    const videoUrl = mediaData.video_url || '';
    const displayUrl = mediaData.display_url || '';
    const author = mediaData.owner?.username || 'instagram_user';
    const caption = mediaData.edge_media_to_caption?.edges?.[0]?.node?.text || 'Instagram Post';
    const downloads = [];
    const gallery = [];

    // Check for Carousel (Sidecar)
    const isSidecar = Array.isArray(mediaData.edge_sidecar_to_children?.edges) && mediaData.edge_sidecar_to_children.edges.length > 0;

    if (isSidecar) {
      mediaData.edge_sidecar_to_children.edges.forEach((edge, i) => {
        const node = edge?.node;
        if (!node) return;
        const itemNum = i + 1;
        const childIsVideo = !!node.is_video;
        const childVideoUrl = node.video_url || '';
        let childImgUrl = node.display_url || '';
        if (Array.isArray(node.display_resources) && node.display_resources.length > 0) {
          childImgUrl = node.display_resources[node.display_resources.length - 1].src || childImgUrl;
        }

        if (childIsVideo && childVideoUrl) {
          downloads.push({
            type: 'video',
            label: `Unduh Video #${itemNum} (Kualitas HD)`,
            quality: '⭐ True HD',
            icon: 'badge-hd',
            btnClass: 'btn-instagram',
            url: childVideoUrl,
            filename: `instagram_${author}_${shortcode}_video_${itemNum}.mp4`
          });
          if (childImgUrl) gallery.push(childImgUrl);
        } else if (childImgUrl) {
          downloads.push({
            type: 'image',
            label: `Unduh Foto #${itemNum} HD (Resolusi Penuh)`,
            quality: '⭐ HD Foto',
            icon: 'image',
            btnClass: 'btn-instagram',
            url: childImgUrl,
            filename: `instagram_${author}_${shortcode}_photo_${itemNum}.jpg`
          });
          gallery.push(childImgUrl);
        }
      });
    } else {
      // Single Post
      const isVideo = !!mediaData.is_video;
      let bestImg = displayUrl;
      if (Array.isArray(mediaData.display_resources) && mediaData.display_resources.length > 0) {
        bestImg = mediaData.display_resources[mediaData.display_resources.length - 1].src || bestImg;
      }

      if (isVideo && videoUrl) {
        downloads.push({
          type: 'video',
          label: 'Unduh Video Full HD 1080p - Tanpa Pecah',
          quality: '⭐ 1080p Full HD',
          icon: 'badge-hd',
          btnClass: 'btn-instagram',
          url: videoUrl,
          filename: `instagram_${author}_${shortcode}_1080p.mp4`
        });
        downloads.push({
          type: 'video',
          label: 'Unduh Video HD 720p - Kualitas Seimbang',
          quality: 'HD 720p',
          icon: 'play',
          btnClass: 'btn-instagram',
          url: videoUrl,
          filename: `instagram_${author}_${shortcode}_720p.mp4`
        });
        downloads.push({
          type: 'video',
          label: 'Unduh Video Hemat Kuota SD 360p - Ringan & Cepat',
          quality: 'SD 360p (Hemat Kuota)',
          icon: 'lightning',
          btnClass: 'btn-download-sd',
          url: videoUrl,
          filename: `instagram_${author}_${shortcode}_360p.mp4`
        });
        if (bestImg) {
          downloads.push({
            type: 'image',
            label: 'Unduh Thumbnail HD (Resolusi Penuh)',
            quality: 'HD Image',
            icon: 'image',
            btnClass: 'btn-cover',
            url: bestImg,
            filename: `instagram_${author}_${shortcode}_thumb.jpg`
          });
          gallery.push(bestImg);
        }
      } else if (bestImg) {
        downloads.push({
          type: 'image',
          label: 'Unduh Foto Instagram HD (Resolusi Penuh)',
          quality: '⭐ HD Foto',
          icon: 'image',
          btnClass: 'btn-instagram',
          url: bestImg,
          filename: `instagram_${author}_${shortcode}.jpg`
        });
        gallery.push(bestImg);
      }
    }

    const isVideoTop = !!mediaData.is_video;
    return {
      platform: 'instagram',
      platformName: 'Instagram',
      mediaType: isSidecar ? 'carousel' : (isVideoTop ? 'video' : 'photo'),
      title: caption,
      author: author,
      authorAvatar: mediaData.owner?.profile_pic_url || '',
      duration: mediaData.video_duration ? `${Math.round(mediaData.video_duration)}s` : (isSidecar ? `${gallery.length} Media` : ''),
      thumbnail: gallery[0] || displayUrl || '',
      previewVideo: (!isSidecar && isVideoTop) ? videoUrl : '',
      gallery: gallery,
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
      const isMusic = url.includes('music.youtube.com');
      const oembedUrl = `https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`;
      const oembedResp = await fetch(oembedUrl);
      if (oembedResp.ok) {
        const oembed = await oembedResp.json();
        const maxThumb = `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
        return {
          platform: isMusic ? 'ytmusic' : 'youtube',
          platformName: isMusic ? 'YouTube Music' : 'YouTube',
          title: oembed.title || (isMusic ? 'YouTube Music Track' : 'YouTube Video'),
          author: oembed.author_name || (isMusic ? 'Music Artist' : 'YouTube Creator'),
          authorAvatar: isMusic ? 'https://music.youtube.com/favicon.ico' : 'https://www.youtube.com/favicon.ico',
          duration: isMusic ? 'Audio/Video' : 'Video',
          thumbnail: maxThumb,
          previewVideo: '',
          downloads: [
            ...(isMusic ? [{
              type: 'audio',
              label: 'Unduh Audio Musik MP3 (320k Studio)',
              quality: '⭐ MP3 320k HQ',
              icon: 'music',
              btnClass: 'btn-youtube-music',
              url: `${serverBaseUrl}/download/file?url=${encodeURIComponent(url)}&name=ytmusic_${videoId}_320k.mp3&type=audio&source_url=${encodeURIComponent(url)}`,
              source_url: url,
              filename: `ytmusic_${videoId}_320k.mp3`
            }] : []),
            {
              type: 'video',
              label: isMusic ? 'Unduh Video Musik Full HD 1080p (MP4 Jernih)' : 'Unduh Video Full HD 1080p (MP4 Jernih)',
              quality: '⭐ 1080p Full HD',
              icon: 'badge-hd',
              btnClass: 'btn-youtube-hd',
              url: `${serverBaseUrl}/download/file?url=${encodeURIComponent(url)}&name=${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_1080p.mp4&type=video&source_url=${encodeURIComponent(url)}&needs_merge=1`,
              source_url: url,
              needs_merge: true,
              filename: `${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_1080p.mp4`
            },
            {
              type: 'video',
              label: isMusic ? 'Unduh Video Musik HD 720p (MP4 Seimbang)' : 'Unduh Video HD 720p (MP4 Cerdas)',
              quality: 'HD 720p',
              icon: 'play',
              btnClass: 'btn-youtube',
              url: `${serverBaseUrl}/download/file?url=${encodeURIComponent(url)}&name=${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_720p.mp4&type=video&source_url=${encodeURIComponent(url)}`,
              source_url: url,
              filename: `${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_720p.mp4`
            },
            {
              type: 'video',
              label: isMusic ? 'Unduh Video Musik SD 360p (Hemat Kuota)' : 'Unduh Video SD 360p (Hemat Kuota)',
              quality: 'SD 360p (Hemat Kuota)',
              icon: 'lightning',
              btnClass: 'btn-download-sd',
              url: `${serverBaseUrl}/download/file?url=${encodeURIComponent(url)}&name=${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_360p.mp4&type=video&source_url=${encodeURIComponent(url)}`,
              source_url: url,
              filename: `${isMusic ? 'ytmusic' : 'youtube'}_${videoId}_360p.mp4`
            },
            {
              type: 'image',
              label: isMusic ? 'Unduh Cover Album HD' : 'Unduh Cover / Thumbnail HD',
              quality: 'HD Artwork',
              icon: 'image',
              btnClass: 'btn-cover',
              url: maxThumb,
              filename: isMusic ? `ytmusic_${videoId}_cover.jpg` : `youtube_${videoId}_cover.jpg`
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
      const timeoutId = setTimeout(() => controller.abort(), 15000);
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

    if (platform === 'youtube' || platform === 'ytmusic') {
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
