<<<<<<< HEAD
/*
=======
﻿/*
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
 * 役割：生徒詳細画面のUI制御
 * 1) 要素取得
 * 2) 点数計算
 * 3) 成績入力イベント
 * 4) 写真アップロード
 * 5) フォーム検証/送信
 * 6) ログアウト
 * 7) 入力エラー解除
 * 8) 初期化
 */

document.addEventListener("DOMContentLoaded", function () {
  

  // 要素取得
  const logoutBtn = document.getElementById("logout-btn");
  const scoreTableBody = document.getElementById('score-table-body');
  const photoBtn = document.getElementById('photo-btn');
  const photoInput = document.getElementById('photo-input');
  const studentPhoto = document.getElementById('student-photo');
  const photoError = document.getElementById('photo-error');
  const studentForm = document.getElementById('student-register-form');
  const validationError = document.getElementById('validation-error');

  // 点数計算
<<<<<<< HEAD
  function getElementValue(el) {
    if (!el) return '';
    if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
      return el.value.trim();
    }
    return (el.textContent || '').trim();
  }

  function setElementValue(el, value) {
    if (!el) return;
    if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
      el.value = value;
      return;
    }
    el.textContent = value;
  }

=======
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
  function calcScore(tr) {
    const inputs = Array.from(tr.querySelectorAll('.score-input'));
    const nums = inputs.map(i => parseInt(i.value, 10)).filter(n => !isNaN(n));
    const sum = nums.reduce((a, b) => a + b, 0);
    const avg = nums.length ? (sum / nums.length).toFixed(1) : '';
<<<<<<< HEAD
    setElementValue(tr.querySelector('.score-sum'), nums.length ? sum : '');
    setElementValue(tr.querySelector('.score-avg'), nums.length ? avg : '');
  }
=======
    tr.querySelector('.score-sum').value = nums.length ? sum : '';
    tr.querySelector('.score-avg').value = nums.length ? avg : '';
  }

>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
  // ログアウト
  logoutBtn.addEventListener('click', () => { window.location.href = 'logout.php'; });

  // 未受験ハイライト
  function highlightUnselectedTests() {
    const existingRows = document.querySelectorAll('#score-table-body tr[data-existing="true"]');
    existingRows.forEach(tr => {
      const typeSelect = tr.querySelector('.score-type');
<<<<<<< HEAD
      if (typeSelect && getElementValue(typeSelect) === '未受験') {
=======
      if (typeSelect && typeSelect.value === '未受験') {
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
        typeSelect.classList.add('select-error');
      }
    });
  }

  highlightUnselectedTests();

  // 成績入力イベント
  scoreTableBody.addEventListener('input', (e) => {
    if (e.target.classList.contains('score-input')) {
      const tr = e.target.closest('tr');
      if (tr) {
        calcScore(tr);
      }
      e.target.classList.remove('input-error');
      return;
    }
    if (e.target.classList.contains('score-date')) {
      e.target.classList.remove('input-error');
    }
  });

  scoreTableBody.addEventListener('change', (e) => {
    if (!e.target.classList.contains('score-type')) {
      return;
    }
    const tr = e.target.closest('tr');
    if (e.target.classList.contains('select-error')) {
      e.target.classList.remove('select-error');
    }
    if (e.target.value === '未受験') {
      if (tr && tr.getAttribute('data-existing') === 'true') {
        e.target.classList.add('select-error');
      }
      if (tr) {
        tr.querySelectorAll('.score-input').forEach(input => {
          input.value = '0';
        });
        calcScore(tr);
      }
    }
  });

  // 写真アップロード
  photoBtn.addEventListener('click', () => {
    photoInput.click();
  });

  photoInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      handlePhotoUpload(file);
    }
  });

  function handlePhotoUpload(file) {
    hidePhotoError();

    // 3MB上限
    if (file.size > 3 * 1024 * 1024) {
      showPhotoError('ファイルサイズは3MB以下にしてください。');
      return;
    }

    // JPEGのみ許可
    if (!file.type.match('image/jpeg') && !file.type.match('image/jpg')) {
      showPhotoError('JPEG形式のファイルを選択してください。');
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      studentPhoto.src = e.target.result;
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

  // フォーム検証/送信
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

  studentForm.addEventListener('submit', function(e) {
    e.preventDefault();

    if (validateForm()) {

      const rows = document.querySelectorAll('#score-table-body tr');
      let hasMeaningfulTest = false;
      rows.forEach(tr => {
        const dateInput = tr.querySelector('.score-date');
        const typeSelect = tr.querySelector('.score-type');
        const scoreInputs = tr.querySelectorAll('.score-input');
        if (!dateInput || !typeSelect || scoreInputs.length === 0) return;
        const anyScoreFilled = Array.from(scoreInputs).some(input => input.value && input.value.trim() !== '');
<<<<<<< HEAD
        if (getElementValue(dateInput) && getElementValue(typeSelect) !== '未受験' && anyScoreFilled) {
=======
        if (dateInput.value.trim() && typeSelect.value !== '未受験' && anyScoreFilled) {
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
          hasMeaningfulTest = true;
        }
      });
      if (!hasMeaningfulTest) {
        const proceed = confirm('テスト情報が未入力です、生徒情報のみ保存しますか');
        if (!proceed) {
          return;
        }
      }

      studentForm.submit();
    }
  });

  document.getElementById('logout-logo').addEventListener('click', function() {
    if (confirm('ログアウトしますか？')) {
      document.getElementById('logout-form').submit();
    }
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

  // 初期化
  function init() {
    const url = new URL(window.location.href);

    // PRGの表示制御
    if (url.searchParams.get('updated') === '1') {
      alert('更新しました。');
      url.searchParams.delete('updated');
      history.replaceState(null, '', url.toString());
    }
    if (url.searchParams.get('saved') === '1') {
      alert('テスト情報を保存しました。');
      url.searchParams.delete('saved');
      history.replaceState(null, '', url.toString());
    }
    if (url.searchParams.get('deleted') === '1') {
      alert('成績を削除しました。各科目0点で表示されます');
      url.searchParams.delete('deleted');
      history.replaceState(null, '', url.toString());
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'validation') {
      validationError.classList.remove('hidden');
    }
    setupInputValidation();
  }
  init();
});



