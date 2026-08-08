var myMap, myMarker = null;
var pointInput = document.getElementById('attraction_coord-input');
var currentPos = [58.604296629775774, 49.667138825401516]; // центр Кирова
window.onload = () => { ymaps.ready(init) }

/**
 * Функция, которая проверяет, являются ли переданные координаты валидными.
 *
 * @function is_coord
 * @param {Array} coordinates - Массив с координатами. Должен содержать два числа.
 * @ {Boolean} Возвращает `true`, если координаты валидны, и `false` в противном случае.
 * @example
 * let coordinates = [1.23, 4.56];
 * let isValid = is_coord(coordinates); // Возвращает true
 */
function is_coord(coordinates) {
    return coordinates && parseFloat(coordinates[0]) && parseFloat(coordinates[1]);
}

/**
 * Функция, которая устанавливает позицию маркера на карте.
 *
 * @function setMarkerPos
 * @param {Array} coordinates - Массив с координатами. Должен содержать два числа.
 * @example
 * let coordinates = [1.23, 4.56];
 * setMarkerPos(coordinates);
 */
function setMarkerPos(coordinates) {
    if (is_coord(coordinates))
        if (!myMarker) {
            myMarker = new ymaps.GeoObject({ geometry: { type: "Point", coordinates } });
            myMap.geoObjects.add(myMarker);
        } else myMarker.geometry.setCoordinates(coordinates);
}

/**
 * Функция, которая обрабатывает ввод пользователя и устанавливает позицию маркера на карте.
 *
 * @function inputFunc
 * @example
 * inputFunc();
 */
function inputFunc() {
    var coords = (pointInput.value)?pointInput.value.split(', '):null;
    if (coords) setMarkerPos(coords);
    else {
        myMap.geoObjects.remove(myMarker);
        myMarker = null;
    }
}

/**
 * Функция, которая инициализирует карту и устанавливает обработчик событий на событие 'click', чтобы установить позицию маркера на карте при клике на карту.
 *
 * @function init
 * @example
 * init();
 */
function init() {
    var coords = (pointInput.value) ? pointInput.value.split(', ') : null;
    myMap = new ymaps.Map("map", {
        center: (is_coord(coords)) ? coords : currentPos,
        zoom: 11
    });
    if (coords) setMarkerPos(coords);

    myMap.events.add('click', e => {
        var coords = e.get('coords');
        setMarkerPos(coords);
        pointInput.value = coords.join(', ');

    });
    pointInput.oninput = inputFunc;
}