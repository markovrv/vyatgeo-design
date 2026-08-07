// Мини-карта в редакторе таксономии «Город» — по образцу
// api/attraction/js/adminMap.js: клик по карте или ручной ввод в поле
// #city_coord одинаково двигают маркер и обновляют значение поля.
var myMap, myMarker = null;
var pointInput = document.getElementById('city_coord');
var currentPos = [58.604296629775774, 49.667138825401516]; // центр Кирова — стартовая точка для любого города
window.onload = () => { ymaps.ready(init) }

function is_coord(coordinates) {
    return coordinates && parseFloat(coordinates[0]) && parseFloat(coordinates[1]);
}

function setMarkerPos(coordinates) {
    if (is_coord(coordinates))
        if (!myMarker) {
            myMarker = new ymaps.GeoObject({ geometry: { type: "Point", coordinates } });
            myMap.geoObjects.add(myMarker);
        } else myMarker.geometry.setCoordinates(coordinates);
}

function inputFunc() {
    var coords = (pointInput.value) ? pointInput.value.split(', ') : null;
    if (coords) setMarkerPos(coords);
    else {
        myMap.geoObjects.remove(myMarker);
        myMarker = null;
    }
}

function init() {
    var coords = (pointInput.value) ? pointInput.value.split(', ') : null;
    myMap = new ymaps.Map("city-map", {
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
