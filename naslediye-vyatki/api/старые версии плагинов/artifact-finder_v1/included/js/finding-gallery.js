// included/finding-gallery.js

// Глобальные переменные для управления галереей
let currentGalleryIndex = 0;
let currentImages = [];
let isModalOpen = false;
let currentScale = 1;
let brightness = 100;
let contrast = 100;
let isDragging = false;
let startX, startY, scrollLeft, scrollTop;
const scaleStep = 0.2;
let zoomEnabled = true;

function switchFindingImage(thumbElement, fullImageUrl, index) {
    const container = thumbElement.closest('.gallery-carousel');
    const mainImg = container.querySelector('.gallery-main img');
    mainImg.src = fullImageUrl;

    const thumbnails = container.querySelectorAll('.gallery-thumb');
    thumbnails.forEach(thumb => {
        thumb.classList.remove('active');
    });

    thumbElement.classList.add('active');
    currentGalleryIndex = index;

    // Сохраняем ссылки на все изображения для модального окна
    currentImages = Array.from(thumbnails).map(thumb =>
        thumb.querySelector('img').src.replace('-150x150', '').replace('-300x300', '')
    );
}

function initImageZoom() {
    const mainImages = document.querySelectorAll('.gallery-main img');

    mainImages.forEach(img => {
        // Удаляем предыдущие обработчики
        img.removeEventListener('mouseenter', handleMouseEnter);
        img.removeEventListener('mousemove', handleMouseMove);
        img.removeEventListener('mouseleave', handleMouseLeave);
        img.removeEventListener('click', handleImageClick);

        // Добавляем новые обработчики
        img.addEventListener('mouseenter', handleMouseEnter);
        img.addEventListener('mousemove', handleMouseMove);
        img.addEventListener('mouseleave', handleMouseLeave);
        img.addEventListener('click', handleImageClick);
    });
}

function handleMouseEnter(e) {
    const img = e.target;
    if (img.naturalWidth <= img.clientWidth && img.naturalHeight <= img.clientHeight) {
        return; // Не увеличиваем если изображение меньше контейнера
    }

    img.style.transform = 'scale(1.5)';
    img.style.cursor = 'zoom-in';
    img.parentElement.style.overflow = 'hidden';
}

function handleMouseMove(e) {
    const img = e.target;
    if (img.style.transform !== 'scale(1.5)') return;

    const container = img.parentElement;
    const rect = container.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    img.style.transformOrigin = `${xPercent}% ${yPercent}%`;
}

function handleMouseLeave(e) {
    const img = e.target;
    img.style.transform = 'scale(1)';
    img.style.transformOrigin = 'center center';
    img.style.cursor = 'pointer';
}

function handleImageClick(e) {
    e.preventDefault();
    openModal(currentGalleryIndex);
}

function openModal(startIndex = 0) {
    if (currentImages.length === 0) return;
    
    // Загружаем настройки перед открытием модального окна
    loadSettings();
    
    isModalOpen = true;
    currentGalleryIndex = startIndex;
    
    const modalHTML = `
        <div class="finding-modal-overlay" id="findingModal">
            <div class="finding-modal-container">
                <!-- Панель управления -->
                <div class="modal-controls">
                    <div class="controls-left">
                        <button class="control-btn" onclick="changeBrightness(-10)" title="Уменьшить яркость (Яркость: ${brightness}%)">
                            <span>🔅</span>
                        </button>
                        <button class="control-btn" onclick="changeBrightness(10)" title="Увеличить яркость (Яркость: ${brightness}%)">
                            <span>🔆</span>
                        </button>
                        <button class="control-btn" onclick="changeContrast(-10)" title="Уменьшить контраст (Контраст: ${contrast}%)">
                            <span>◐</span>
                        </button>
                        <button class="control-btn" onclick="changeContrast(10)" title="Увеличить контраст (Контраст: ${contrast}%)">
                            <span>◑</span>
                        </button>
                        <button class="control-btn" onclick="resetSettings()" title="Сбросить настройки фильтров">
                            <span>🔄</span>
                        </button>
                    </div>
                    <div class="controls-center fordesktop">
                        <span class="image-counter">${currentGalleryIndex + 1} / ${currentImages.length}</span>
                        <span class="zoom-indicator" id="zoomIndicator" style="margin-left: 15px; color: #ccc; font-size: 0.9em;">
                            ${Math.round(currentScale * 100)}%
                        </span>
                        <span class="filter-indicator" id="filterIndicator" style="margin-left: 15px; color: #ccc; font-size: 0.9em;">
                            Ярк: ${brightness}% Контр: ${contrast}%
                        </span>
                    </div>
                    <div class="controls-right">
                        <button class="control-btn fordesktop" onclick="toggleZoom()" title="Включить/выключить зум колесиком (Z) - ${zoomEnabled ? 'ВКЛ' : 'ВЫКЛ'}">
                            <span id="zoomToggleIcon">${zoomEnabled ? '🔍' : '🚫'}</span>
                        </button>
                        <button class="control-btn fordesktop" onclick="zoomOut()" title="Уменьшить (-)">
                            <span>➖</span>
                        </button>
                        <button class="control-btn fordesktop" onclick="resetZoom()" title="Сбросить масштаб (0)">
                            <span>⏹️</span>
                        </button>
                        <button class="control-btn fordesktop" onclick="zoomIn()" title="Увеличить (+)">
                            <span>➕</span>
                        </button>
                        <button class="control-btn" onclick="closeModal()" title="Закрыть (Esc)">
                            <span>✕</span>
                        </button>
                    </div>
                </div>
                
                <!-- Область изображения -->
                <div class="modal-image-container">
                    <button class="nav-btn nav-prev" onclick="prevImage()">‹</button>
                    
                    <div class="modal-image-wrapper">
                        <img id="modalImage" src="${currentImages[currentGalleryIndex]}" 
                             alt="Просмотр изображения ${currentGalleryIndex + 1}"
                             style="display: block; transform-origin: center center; transition: transform 0.3s ease; filter: brightness(${brightness}%) contrast(${contrast}%);">
                    </div>
                    
                    <button class="nav-btn nav-next" onclick="nextImage()">›</button>
                </div>
                
                <!-- Миниатюры внизу -->
                <div class="modal-thumbnails">
                    ${currentImages.map((img, index) => `
                        <div class="modal-thumb ${index === currentGalleryIndex ? 'active' : ''}" 
                             onclick="switchModalImage(${index})">
                            <img src="${img}" alt="Миниатюра ${index + 1}" style="filter: brightness(${brightness}%) contrast(${contrast}%);">
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
    
    // Ждем немного для инициализации DOM
    setTimeout(() => {
        // Инициализация состояния
        resetZoom();
        
        // Инициализируем зум колесиком и движение мыши
        initModalWheelZoom();
        initModalImageHover();
        
        // Обновляем индикаторы
        updateZoomIndicator();
        updateFilterIndicator();
    }, 50);
    
    // Добавляем обработчики клавиш
    document.addEventListener('keydown', handleKeyDown);
}

function closeModal() {
    const modal = document.getElementById('findingModal');
    if (modal) {
        removeModalWheelZoom();
        removeModalImageHover();
        modal.remove();
    }
    document.body.style.overflow = '';
    isModalOpen = false;
    document.removeEventListener('keydown', handleKeyDown);
}

function resetSettings() {
    brightness = 100;
    contrast = 100;
    saveSettings();
    applyFilters();
    showNotification('Настройки сброшены');
}

function loadSettings() {
    try {
        const savedBrightness = localStorage.getItem('findingGallery_brightness');
        const savedContrast = localStorage.getItem('findingGallery_contrast');
        const savedZoomEnabled = localStorage.getItem('findingGallery_zoomEnabled');
        
        if (savedBrightness) brightness = parseInt(savedBrightness);
        if (savedContrast) contrast = parseInt(savedContrast);
        if (savedZoomEnabled) zoomEnabled = savedZoomEnabled === 'true';
    } catch (e) {
        console.log('Не удалось загрузить настройки из localStorage:', e);
    }
}

function saveSettings() {
    try {
        localStorage.setItem('findingGallery_brightness', brightness);
        localStorage.setItem('findingGallery_contrast', contrast);
        localStorage.setItem('findingGallery_zoomEnabled', zoomEnabled);
    } catch (e) {
        console.log('Не удалось сохранить настройки в localStorage:', e);
    }
}

function handleKeyDown(e) {
    if (!isModalOpen) return;
    
    switch(e.key) {
        case 'Escape':
            closeModal();
            break;
        case 'ArrowLeft':
            prevImage();
            break;
        case 'ArrowRight':
            nextImage();
            break;
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
        case '0':
            resetZoom();
            break;
        case 'z':
        case 'Z':
            toggleZoom();
            break;
        case 'r':
        case 'R':
            resetSettings();
            break;
        case 'b':
            changeBrightness(-10);
            break;
        case 'B':
            changeBrightness(10);
            break;
        case 'c':
            changeContrast(-10);
            break;
        case 'C':
            changeContrast(10);
            break;
    }
}

function switchModalImage(index) {
    if (index < 0 || index >= currentImages.length) return;
    
    currentGalleryIndex = index;
    const modalImage = document.getElementById('modalImage');
    modalImage.src = currentImages[index];
    
    // Применяем текущие настройки фильтров к новому изображению
    modalImage.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
    
    // Обновляем счетчик
    const counter = document.querySelector('.image-counter');
    if (counter) {
        counter.textContent = `${index + 1} / ${currentImages.length}`;
    }
    
    // Обновляем активную миниатюру и применяем фильтры к миниатюрам
    document.querySelectorAll('.modal-thumb').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
        const thumbImg = thumb.querySelector('img');
        if (thumbImg) {
            thumbImg.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
        }
    });
    
    // Сбрасываем только масштаб для нового изображения
    resetZoom();
    
    // Переинициализируем обработчики для нового изображения
    setTimeout(() => {
        initModalImageHover();
        updateFilterIndicator();
    }, 100);
}

function prevImage() {
    const newIndex = (currentGalleryIndex - 1 + currentImages.length) % currentImages.length;
    switchModalImage(newIndex);
}

function nextImage() {
    const newIndex = (currentGalleryIndex + 1) % currentImages.length;
    switchModalImage(newIndex);
}

function zoomIn() {
    const modalImage = document.getElementById('modalImage');
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    
    if (modalImage && imageWrapper) {
        // Получаем текущую позицию курсора для плавного увеличения
        const rect = imageWrapper.getBoundingClientRect();
        const x = (rect.width / 2); // Центр по умолчанию
        const y = (rect.height / 2);
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        modalImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
        currentScale = Math.min(5, currentScale + scaleStep);
        modalImage.style.transform = `scale(${currentScale})`;
        modalImage.style.cursor = currentScale > 1 ? 'zoom-in' : 'default';
        
        updateZoomIndicator();
    }
}

function zoomOut() {
    const modalImage = document.getElementById('modalImage');
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    
    if (modalImage && imageWrapper) {
        // Получаем текущую позицию курсора для плавного уменьшения
        const rect = imageWrapper.getBoundingClientRect();
        const x = (rect.width / 2); // Центр по умолчанию
        const y = (rect.height / 2);
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        modalImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
        currentScale = Math.max(0.2, currentScale - scaleStep);
        modalImage.style.transform = `scale(${currentScale})`;
        modalImage.style.cursor = currentScale > 1 ? 'zoom-in' : 'default';
        
        updateZoomIndicator();
    }
}

function resetZoom() {
    currentScale = 1;
    
    const modalImage = document.getElementById('modalImage');
    if (modalImage) {
        modalImage.style.transform = `scale(${currentScale})`;
        modalImage.style.transformOrigin = 'center center';
        modalImage.style.cursor = 'default';
        updateZoomIndicator();
    }
}

function centerImage(wrapper, image) {
    if (!wrapper || !image) return;
    
    const wrapperRect = wrapper.getBoundingClientRect();
    const imageRect = image.getBoundingClientRect();
    
    // Вычисляем центрирующую позицию
    const targetScrollLeft = (imageRect.width - wrapperRect.width) / 2;
    const targetScrollTop = (imageRect.height - wrapperRect.height) / 2;
    
    wrapper.scrollLeft = targetScrollLeft;
    wrapper.scrollTop = targetScrollTop;
}

function applyZoom() {
    const modalImage = document.getElementById('modalImage');
    
    if (modalImage) {
        modalImage.style.transform = `scale(${currentScale})`;
        modalImage.style.cursor = currentScale > 1 ? 'zoom-in' : 'default';
        
        // Сбрасываем transformOrigin при изменении масштаба
        modalImage.style.transformOrigin = 'center center';
        
        updateZoomIndicator();
    }
}

function toggleZoom() {
    zoomEnabled = !zoomEnabled;
    const zoomToggleIcon = document.getElementById('zoomToggleIcon');
    if (zoomToggleIcon) {
        zoomToggleIcon.textContent = zoomEnabled ? '🔍' : '🚫';
    }
    
    // Обновляем title кнопки
    const zoomButton = document.querySelector('[onclick="toggleZoom()"]');
    if (zoomButton) {
        zoomButton.title = `Включить/выключить зум колесиком (Z) - ${zoomEnabled ? 'ВКЛ' : 'ВЫКЛ'}`;
    }
    
    saveSettings();
    showNotification(zoomEnabled ? 'Зум колесиком включен' : 'Зум колесиком выключен');
}

function updateZoomIndicator() {
    const zoomIndicator = document.getElementById('zoomIndicator');
    if (zoomIndicator) {
        zoomIndicator.textContent = `${Math.round(currentScale * 100)}%`;
    }
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 10000;
        font-size: 14px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 2000);
}

function showTemporaryValue(message) {
    // Удаляем предыдущее уведомление о значении
    const existingNotification = document.getElementById('valueNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.id = 'valueNotification';
    notification.style.cssText = `
        position: fixed;
        top: 120px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 16px;
        border-radius: 5px;
        z-index: 10000;
        font-size: 14px;
        font-family: monospace;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 1000);
}

function initImageDragging(img) {
    const container = img.parentElement;
    
    // Удаляем предыдущие обработчики
    removeImageDragging(img);
    
    const onMouseDown = function(e) {
        if (currentScale <= 1) return;
        
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
        
        img.style.cursor = 'grabbing';
        container.style.cursor = 'grabbing';
        container.classList.add('dragging');
        e.preventDefault();
    };
    
    const onMouseMove = function(e) {
        if (!isDragging) return;
        
        const deltaX = startX - e.clientX;
        const deltaY = startY - e.clientY;
        
        // Плавное движение с инерцией
        container.scrollLeft = scrollLeft + deltaX;
        container.scrollTop = scrollTop + deltaY;
    };
    
    const onMouseUp = function() {
        isDragging = false;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        container.style.cursor = 'default';
        container.classList.remove('dragging');
    };
    
    const onMouseLeave = function() {
        isDragging = false;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        container.style.cursor = 'default';
        container.classList.remove('dragging');
    };
    
    // Сохраняем ссылки на функции для последующего удаления
    img._onMouseDown = onMouseDown;
    img._onMouseMove = onMouseMove;
    img._onMouseUp = onMouseUp;
    img._onMouseLeave = onMouseLeave;
    
    // Добавляем обработчики
    img.addEventListener('mousedown', onMouseDown);
    container.addEventListener('mousemove', onMouseMove);
    container.addEventListener('mouseup', onMouseUp);
    container.addEventListener('mouseleave', onMouseLeave);
}

function changeBrightness(value) {
    brightness = Math.max(0, Math.min(200, brightness + value));
    applyFilters();
    saveSettings();
    
    // Показываем текущее значение
    showTemporaryValue(`Яркость: ${brightness}%`);
}

function changeContrast(value) {
    contrast = Math.max(0, Math.min(200, contrast + value));
    applyFilters();
    saveSettings();
    
    // Показываем текущее значение
    showTemporaryValue(`Контраст: ${contrast}%`);
}

function resetFilters() {
    brightness = 100;
    contrast = 100;
    applyFilters();
}

function applyFilters() {
    const modalImage = document.getElementById('modalImage');
    if (modalImage) {
        modalImage.style.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
    }
}

function initModalWheelZoom() {
    const modalImage = document.getElementById('modalImage');
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    
    if (!modalImage || !imageWrapper) return;
    
    const onWheel = function(e) {
        if (!zoomEnabled) return;
        
        e.preventDefault();
        
        // Получаем позицию мыши относительно wrapper
        const rect = imageWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        // Устанавливаем точку трансформации под курсором
        modalImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
        
        // Определяем направление скролла
        const delta = Math.sign(e.deltaY) * -1;
        
        if (delta > 0) {
            // Увеличиваем
            currentScale = Math.min(5, currentScale + scaleStep);
        } else {
            // Уменьшаем
            currentScale = Math.max(0.2, currentScale - scaleStep);
        }
        
        // Применяем масштаб
        modalImage.style.transform = `scale(${currentScale})`;
        modalImage.style.cursor = currentScale > 1 ? 'zoom-in' : 'default';
        
        updateZoomIndicator();
    };
    
    // Добавляем обработчик колесика мыши
    imageWrapper.addEventListener('wheel', onWheel, { passive: false });
    
    // Сохраняем ссылку для последующего удаления
    imageWrapper._onWheel = onWheel;
}

function removeModalWheelZoom() {
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    if (imageWrapper && imageWrapper._onWheel) {
        imageWrapper.removeEventListener('wheel', imageWrapper._onWheel);
        delete imageWrapper._onWheel;
    }
}

function removeImageDragging(img) {
    const container = img.parentElement;
    
    if (img._onMouseDown) {
        img.removeEventListener('mousedown', img._onMouseDown);
        container.removeEventListener('mousemove', img._onMouseMove);
        container.removeEventListener('mouseup', img._onMouseUp);
        container.removeEventListener('mouseleave', img._onMouseLeave);
        container.classList.remove('dragging');
    }
    
    // Очищаем ссылки
    delete img._onMouseDown;
    delete img._onMouseMove;
    delete img._onMouseUp;
    delete img._onMouseLeave;
}

function removeModalImageHover() {
    const modalImage = document.getElementById('modalImage');
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    
    if (modalImage && modalImage._onMouseMove && imageWrapper) {
        imageWrapper.removeEventListener('mousemove', modalImage._onMouseMove);
        imageWrapper.removeEventListener('mouseleave', modalImage._onMouseLeave);
        
        delete modalImage._onMouseMove;
        delete modalImage._onMouseLeave;
    }
}

function initModalImageHover() {
    const modalImage = document.getElementById('modalImage');
    const imageWrapper = document.querySelector('.modal-image-wrapper');
    
    if (!modalImage || !imageWrapper) return;
    
    // Удаляем предыдущие обработчики
    removeModalImageHover();
    
    const onMouseMove = function(e) {
        if (currentScale <= 1) return;
        
        const rect = imageWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        modalImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
    };
    
    const onMouseLeave = function() {
        // При уходе мыши возвращаем центрирование
        modalImage.style.transformOrigin = 'center center';
    };
    
    // Сохраняем ссылки на функции
    modalImage._onMouseMove = onMouseMove;
    modalImage._onMouseLeave = onMouseLeave;
    
    // Добавляем обработчики
    imageWrapper.addEventListener('mousemove', onMouseMove);
    imageWrapper.addEventListener('mouseleave', onMouseLeave);
}

function initImageDragging(img) {
    const container = img.parentElement;

    // Удаляем предыдущие обработчики
    removeImageDragging(img);

    const onMouseDown = function (e) {
        if (currentScale <= 1) return;

        isDragging = true;
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;

        img.style.cursor = 'grabbing';
        container.style.cursor = 'grabbing';
        e.preventDefault();
    };

    const onMouseMove = function (e) {
        if (!isDragging) return;

        const x = e.pageX - container.offsetLeft;
        const y = e.pageY - container.offsetTop;
        const walkX = (x - startX) * 3; // Увеличиваем коэффициент для более плавного движения
        const walkY = (y - startY) * 3;

        container.scrollLeft = scrollLeft - walkX;
        container.scrollTop = scrollTop - walkY;
    };

    const onMouseUp = function () {
        isDragging = false;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        container.style.cursor = 'default';
    };

    const onMouseLeave = function () {
        isDragging = false;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        container.style.cursor = 'default';
    };

    // Сохраняем ссылки на функции для последующего удаления
    img._onMouseDown = onMouseDown;
    img._onMouseMove = onMouseMove;
    img._onMouseUp = onMouseUp;
    img._onMouseLeave = onMouseLeave;

    // Добавляем обработчики
    img.addEventListener('mousedown', onMouseDown);
    container.addEventListener('mousemove', onMouseMove);
    container.addEventListener('mouseup', onMouseUp);
    container.addEventListener('mouseleave', onMouseLeave);
}

function updateFilterIndicator() {
    const filterIndicator = document.getElementById('filterIndicator');
    if (filterIndicator) {
        filterIndicator.textContent = `Ярк: ${brightness}% Контр: ${contrast}%`;
    }
    
    // Обновляем title кнопок
    const brightnessDown = document.querySelector('[onclick="changeBrightness(-10)"]');
    const brightnessUp = document.querySelector('[onclick="changeBrightness(10)"]');
    const contrastDown = document.querySelector('[onclick="changeContrast(-10)"]');
    const contrastUp = document.querySelector('[onclick="changeContrast(10)"]');
    
    if (brightnessDown) brightnessDown.title = `Уменьшить яркость (Яркость: ${brightness}%)`;
    if (brightnessUp) brightnessUp.title = `Увеличить яркость (Яркость: ${brightness}%)`;
    if (contrastDown) contrastDown.title = `Уменьшить контраст (Контраст: ${contrast}%)`;
    if (contrastUp) contrastUp.title = `Увеличить контраст (Контраст: ${contrast}%)`;
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    // Загружаем настройки при загрузке страницы
    loadSettings();
    
    // Активируем первую миниатюру
    const carousels = document.querySelectorAll('.gallery-carousel');
    carousels.forEach(carousel => {
        const firstThumb = carousel.querySelector('.gallery-thumb');
        if (firstThumb) {
            firstThumb.classList.add('active');
            
            // Сохраняем все изображения для модального окна
            const thumbnails = carousel.querySelectorAll('.gallery-thumb');
            currentImages = Array.from(thumbnails).map(thumb => 
                thumb.querySelector('img').src.replace('-150x150', '').replace('-300x300', '')
            );
        }
    });
    
    // Инициализируем зум для главных изображений
    initImageZoom();
    
    // Переинициализация при смене изображения
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                setTimeout(initImageZoom, 100);
            }
        });
    });
    
    document.querySelectorAll('.gallery-main img').forEach(img => {
        observer.observe(img, { attributes: true });
    });
});