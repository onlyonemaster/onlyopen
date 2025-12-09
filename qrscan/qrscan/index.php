<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>큐알모음</title>
  <style>
    body {
      font-family: 'Noto Sans KR', sans-serif;
      background: #f7fafd;
      margin: 0;
      padding: 0;
    }
    header {
      background: #4285f4;
      color: white;
      padding: 16px;
      text-align: center;
      font-size: 20px;
      font-weight: bold;
    }
    .container {
      padding: 16px;
      padding-bottom: 80px;
    }
    .qr-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .qr-item {
      display: flex;
      align-items: center;
      background: white;
      padding: 12px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      cursor: pointer;
    }
    .qr-item img {
      width: 64px;
      height: 64px;
      margin-right: 16px;
      border-radius: 8px;
    }
    .qr-item h4 {
      margin: 0;
      font-size: 16px;
    }
    .qr-item p {
      margin: 4px 0 0 0;
      font-size: 14px;
      color: #666;
    }
    .add-btn {
      position: fixed;
      right: 16px;
      bottom: 80px;
      background: #34a853;
      color: white;
      border: none;
      padding: 12px 16px;
      border-radius: 999px;
      font-size: 18px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      cursor: pointer;
      z-index: 1000;
    }
    input, textarea, select {
      width: 100%;
      margin-bottom: 12px;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 14px;
    }
    .modal {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 16px;
      width: 90%;
      max-width: 400px;
      position: relative;
    }
    .modal-content .close-btn {
      position: absolute;
      top: 10px;
      right: 14px;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #888;
    }
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      display: flex;
      justify-content: space-around;
      padding: 10px 0;
      background: #fff;
      border-top: 1px solid #ccc;
      z-index: 999;
    }
    .footer button {
      background: none;
      border: none;
      font-size: 14px;
      cursor: pointer;
    }
    video { width: 100%; border-radius: 12px; }
  </style>
</head>
<body>
  <header>📱 나의 큐알</header>
  <div class="container">
    <input type="text" id="searchInput" placeholder="이름, 메모, 카테고리 검색..." oninput="renderQRList()" />
    <div id="qrList" class="qr-list"></div>
  </div>
  <button class="add-btn" onclick="openModal()">＋</button>

  <div class="footer">
    <button onclick="alert('전체보기 기능 준비 중')">전체보기</button>
    <button onclick="alert('카테고리 보기 준비 중')">카테고리</button>
    <button onclick="location.href='auth.php?logout=1'">로그아웃</button>
  </div>

  <div id="modal" class="modal" style="display:none">
    <div class="modal-content">
      <button class="close-btn" onclick="closeModal()">×</button>
      <h3>QR 코드 등록</h3>
      <input id="titleInput" placeholder="제목 입력" />
      <textarea id="memoInput" placeholder="메모 입력"></textarea>
      <input id="linkInput" placeholder="링크 (https://...)" />
      <select id="categoryInput">
        <option>카테고리 선택</option>
        <option>회원권</option>
        <option>출입증</option>
        <option>명함</option>
      </select>
      <input type="file" accept="image/*" id="imageInput" onchange="autoReadQR()" />
      <button onclick="startCameraScan()">카메라로 스캔</button>
      <button id="saveButton" onclick="saveQR()">저장</button>
    </div>
  </div>

  <div id="cameraModal" class="modal" style="display:none">
    <div class="modal-content">
      <button class="close-btn" onclick="stopCameraScan()">×</button>
      <video id="video" autoplay></video>
    </div>
  </div>

  <div id="detailModal" class="modal" style="display:none">
    <div class="modal-content" id="detailContent"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js" defer></script>
  <script>
    const API_BASE_URL = '/api';
    const USER_ID = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
    const modal = document.getElementById('modal');
    const titleInput = document.getElementById('titleInput');
    const memoInput = document.getElementById('memoInput');
    const linkInput = document.getElementById('linkInput');
    const categoryInput = document.getElementById('categoryInput');
    const imageInput = document.getElementById('imageInput');
    const searchInput = document.getElementById('searchInput');
    const qrList = document.getElementById('qrList');
    const cameraModal = document.getElementById('cameraModal');
    const detailModal = document.getElementById('detailModal');
    const detailContent = document.getElementById('detailContent');
    const video = document.getElementById('video');
    const saveButton = document.getElementById('saveButton');
    let videoStream;
    let currentEditId = null;

    async function fetchQRList(searchTerm = '') {
      try {
        const response = await fetch(`${API_BASE_URL}/qr_codes${searchTerm ? `?search=${encodeURIComponent(searchTerm)}` : ''}`);
        return await response.json();
      } catch (error) {
        console.error('Error fetching QR codes:', error);
        return [];
      }
    }

    async function saveQR() {
      const title = titleInput.value;
      const memo = memoInput.value;
      const link = linkInput.value;
      const category = categoryInput.value;
      const file = imageInput.files[0];

      if (!file) {
        alert('QR 코드 이미지를 선택해주세요.');
        return;
      }

      const reader = new FileReader();
      reader.onload = async function (e) {
        const qrData = {
          title,
          memo,
          link,
          category,
          qr_image: e.target.result
        };

        try {
          if (currentEditId) {
            await fetch(`${API_BASE_URL}/qr_codes/${currentEditId}`, {
              method: 'PUT',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(qrData)
            });
          } else {
            await fetch(`${API_BASE_URL}/qr_codes`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(qrData)
            });
          }
          renderQRList();
          closeModal();
        } catch (error) {
          console.error('Error saving QR code:', error);
          alert('저장 중 오류가 발생했습니다.');
        }
      };
      reader.readAsDataURL(file);
    }

    async function deleteQR(id) {
      try {
        await fetch(`${API_BASE_URL}/qr_codes/${id}`, {
          method: 'DELETE'
        });
        renderQRList();
      } catch (error) {
        console.error('Error deleting QR code:', error);
        alert('삭제 중 오류가 발생했습니다.');
      }
    }

    async function renderQRList() {
      const search = searchInput.value;
      const data = await fetchQRList(search);
      qrList.innerHTML = '';
      data.forEach(item => {
        const el = document.createElement('div');
        el.className = 'qr-item';
        el.innerHTML = `
          <img src="${item.qr_image}" />
          <div>
            <h4>${item.title}</h4>
            <p>${item.memo}</p>
          </div>
        `;
        el.onclick = () => openDetail(item);
        qrList.appendChild(el);
      });
    }

    function openEditModal(item) {
      currentEditId = item.id;
      openModal();
      titleInput.value = item.title;
      memoInput.value = item.memo;
      linkInput.value = item.link;
      categoryInput.value = item.category;

      saveButton.textContent = '수정 저장';
      saveButton.onclick = saveQR;
    }

    function openDetail(item) {
      detailContent.innerHTML = `
        <img src="${item.qr_image}" style="width:100%;border-radius:10px;" />
        <p><strong>제목:</strong> ${item.title}</p>
        <p><strong>메모:</strong> ${item.memo}</p>
        <p><strong>카테고리:</strong> ${item.category}</p>
        <p><strong>링크:</strong> <a href="${item.link}" target="_blank">${item.link}</a></p>
        <button onclick="navigator.clipboard.writeText('${item.link}')">링크 복사</button>
        <button onclick="navigator.share ? navigator.share({ title: '${item.title}', url: '${item.link}' }) : alert('${item.link}')">공유</button>
        <button onclick="openEditModal(${JSON.stringify(item)})">수정</button>
        <button onclick="deleteQR(${item.id}); closeDetail();">삭제</button>
        <button onclick="closeDetail()">닫기</button>
      `;
      detailModal.style.display = 'flex';
    }

    function openModal() { 
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
        titleInput.value = '';
        memoInput.value = '';
        linkInput.value = '';
        categoryInput.value = '';
        imageInput.value = '';
        saveButton.textContent = '저장';
        saveButton.onclick = saveQR;
        currentEditId = null;
    }

    function autoReadQR() {
      const file = imageInput.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const code = jsQR(imageData.data, canvas.width, canvas.height);
          if (code) {
            linkInput.value = code.data;
          }
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    function startCameraScan() {
      cameraModal.style.display = 'flex';
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
          videoStream = stream;
          video.srcObject = stream;
          scanLoop();
        });
    }

    function scanLoop() {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      function scan() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const code = jsQR(imageData.data, canvas.width, canvas.height);
          if (code) {
            linkInput.value = code.data;
            stopCameraScan();
          } else {
            requestAnimationFrame(scan);
          }
        } else {
          requestAnimationFrame(scan);
        }
      }
      scan();
    }

    function stopCameraScan() {
      if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
      }
      cameraModal.style.display = 'none';
    }

    window.openModal = openModal;
    window.closeModal = closeModal;
    window.startCameraScan = startCameraScan;
    window.saveQR = saveQR;
    window.autoReadQR = autoReadQR;
    window.stopCameraScan = stopCameraScan;
    window.renderQRList = renderQRList;
    window.openEditModal = openEditModal;
    window.openDetail = openDetail;
    window.deleteQR = deleteQR;
    window.closeDetail = closeDetail;

    window.onload = renderQRList;
  </script>
</body>
</html>