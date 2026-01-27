/*
 * 役割：生徒一覧/登録画面のUI制御
 * 1) 要素取得
 * 2) 写真アップロード
 * 3) 入力チェック
 * 4) イベント（ログアウト/送信/削除）
 * 5) タブ切り替え
 * 6) 検索クリア
 * 7) 生年月日調整
 * 8) 初期化
 */

document.addEventListener("DOMContentLoaded", function () {

  // 要素取得
  const studentTableBody = document.getElementById("student-table-body");
  const logoutBtn = document.getElementById("logout-btn");
  const tabList = document.getElementById("tab-list");
  const tabRegister = document.getElementById("tab-register");
  const tabContentList = document.getElementById("tab-content-list");
  const tabContentRegister = document.getElementById("tab-content-register");
  const photoBtn = document.getElementById("photo-btn");
  const photoInput = document.getElementById("photo-input");
  const studentPhoto = document.getElementById("student-photo");
  const photoError = document.getElementById("photo-error");
  const studentForm = document.getElementById("student-register-form");
  const validationError = document.getElementById("validation-error");
  const searchInput = document.getElementById("search-name");
  const searchClear = document.getElementById("search-clear");
  const searchForm = document.querySelector("form.search-box");

  // 写真アップロード
  function handlePhotoUpload(file) {

    // 3MB上限
    if (file.size > 3145728) {
      showPhotoError('ファイルサイズは3MB以下にしてください');
      return;
    }

    // JPEGのみ許可
    if (!file.type.match('image/jpeg') && !file.type.match('image/jpg')) {
      showPhotoError('JPEG形式のファイルを選択してください');
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      studentPhoto.src = e.target.result;
      hidePhotoError();
    };
    reader.readAsDataURL(file);
  }

  function showPhotoError(message) {
    photoError.textContent = message;
    photoError.classList.remove('hidden');
  }

  function hidePhotoError() {
    photoError.classList.add('hidden');
  }

  // 入力チェック
  function validateForm() {
    let isValid = true;
    const requiredFields = [
      { id: 'last-name', type: 'input' },
      { id: 'first-name', type: 'input' },
      { id: 'last-name-kana', type: 'input' },
      { id: 'first-name-kana', type: 'input' },
      { id: 'class-select', type: 'select' },
      { id: 'gender-select', type: 'select' },
      { id: 'class-number', type: 'select' },
      { id: 'birth-year', type: 'select' },
      { id: 'birth-month', type: 'select' },
      { id: 'birth-day', type: 'select' }
    ];

    resetValidationErrors();

    requiredFields.forEach(field => {
      const element = document.getElementById(field.id);
      if (!element.value.trim()) {
        isValid = false;
        showFieldError(element, field.type);
      }
    });

    if (!isValid) {
      validationError.classList.remove('hidden');
    } else {
      validationError.classList.add('hidden');
    }

    return isValid;
  }

  function showFieldError(element, type) {
    if (type === 'input') {
      element.classList.add('input-error');
    } else if (type === 'select') {
      element.classList.add('select-error');
    }
  }

  function resetValidationErrors() {
    const errorElements = document.querySelectorAll('.input-error, .select-error');
    errorElements.forEach(element => {
      element.classList.remove('input-error', 'select-error');
    });
    validationError.classList.add('hidden');
  }

  // イベント
  document.getElementById('logout-logo').addEventListener('click', function() {
    if (confirm('ログアウトしますか？')) {
      document.getElementById('logout-form').submit();
    }
  });

  photoBtn.addEventListener('click', () => photoInput.click());
  photoInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
      handlePhotoUpload(e.target.files[0]);
    }
  });

  logoutBtn.addEventListener('click', () => {
    if (confirm('ログアウトしますか？')) {
      document.getElementById('logout-form').submit();
    }
  });

  function submitForm(e) {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    // 検証後は通常送信
    studentForm.submit();
  }

  studentForm.addEventListener('submit', submitForm);
  const registerBtn = document.getElementById('register-btn');
  if (registerBtn) {
    registerBtn.addEventListener('click', submitForm);
  }

  studentTableBody.addEventListener('click', e => {
    const deleteBtn = e.target.closest('.delete-btn');
    if (!deleteBtn) {
      return;
    }
    if (!confirm("本当に削除しますか？\nこの生徒のすべての情報（基本情報、テスト成績、写真など）が完全に削除されます。")) {
      return;
    }
    const studentId = deleteBtn.getAttribute('data-student-id');
    if (studentId) {
      document.getElementById('delete-student-id').value = studentId;
      document.getElementById('delete-student-form').submit();
    }
  });

  // タブ切り替え
  tabList.addEventListener('click', () => {
    tabList.classList.add('active');
    tabRegister.classList.remove('active');
    tabContentList.classList.remove('hidden');
    tabContentRegister.classList.add('hidden');
  });
  tabRegister.addEventListener('click', () => {
    tabRegister.classList.add('active');
    tabList.classList.remove('active');
    tabContentList.classList.add('hidden');
    tabContentRegister.classList.remove('hidden');
  });

  // 入力エラー解除
  function setupInputValidation() {
    const inputFields = document.querySelectorAll('#student-register-form input, #student-register-form select');
    inputFields.forEach(field => {
      field.addEventListener('input', function() {
        if (this.classList.contains('input-error') || this.classList.contains('select-error')) {
          this.classList.remove('input-error', 'select-error');
        }

        if (validateForm()) {
          validationError.classList.add('hidden');
        }
      });
    });
  }

  // 検索クリア
  function updateSearchClearVisibility() {
    if (!searchInput || !searchClear) {
      return;
    }
    if (searchInput.value.trim() !== '') {
      searchClear.classList.add('visible');
    } else {
      searchClear.classList.remove('visible');
    }
  }

  // 生年月日調整
  function updateBirthDayOptions() {
    const yearSelect = document.getElementById('birth-year');
    const monthSelect = document.getElementById('birth-month');
    const daySelect = document.getElementById('birth-day');
    if (!yearSelect || !monthSelect || !daySelect) {
      return;
    }

    const yearValue = parseInt(yearSelect.value, 10);
    const monthValue = parseInt(monthSelect.value, 10);
    const currentDay = parseInt(daySelect.value, 10);

    let maxDay = 31;
    if (!isNaN(monthValue)) {
      if (monthValue === 2) {
        if (!isNaN(yearValue)) {
          const isLeap = (yearValue % 4 === 0 && yearValue % 100 !== 0) || (yearValue % 400 === 0);
          maxDay = isLeap ? 29 : 28;
        } else {
          maxDay = 29;
        }
      } else if ([4, 6, 9, 11].includes(monthValue)) {
        maxDay = 30;
      }
    }

    daySelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '日';
    daySelect.appendChild(placeholder);

    for (let i = 1; i <= maxDay; i++) {
      const opt = document.createElement('option');
      opt.value = String(i);
      opt.textContent = String(i);
      daySelect.appendChild(opt);
    }

    if (!isNaN(currentDay)) {
      const adjusted = Math.min(currentDay, maxDay);
      daySelect.value = String(adjusted);
    }
  }

  // 初期化
  function init() {

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'validation') {
      validationError.classList.remove('hidden');
    }
    if (urlParams.get('tab') === 'register') {
      tabRegister.classList.add('active');
      tabList.classList.remove('active');
      tabContentList.classList.add('hidden');
      tabContentRegister.classList.remove('hidden');
    }

    setupInputValidation();
    updateSearchClearVisibility();
    updateBirthDayOptions();
    const birthYear = document.getElementById('birth-year');
    const birthMonth = document.getElementById('birth-month');
    if (birthYear) {
      birthYear.addEventListener('change', updateBirthDayOptions);
    }
    if (birthYear) {
      birthYear.addEventListener('change', updateBirthDayOptions);
    }
    if (birthMonth) {
      birthMonth.addEventListener('change', updateBirthDayOptions);
    }
    if (searchInput) {
      searchInput.addEventListener('input', updateSearchClearVisibility);
    }
    if (searchClear) {
      searchClear.addEventListener('click', () => {
        searchInput.value = '';
        updateSearchClearVisibility();
        const url = new URL(window.location.href);
        url.searchParams.delete('q');
        window.location.href = url.pathname + (url.search ? url.search : '');
      });
    }

  }
  init();
});



