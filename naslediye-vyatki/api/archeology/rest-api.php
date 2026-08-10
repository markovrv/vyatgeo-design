<?php
/**
 * REST API для модуля «Памятники археологии Кировской области»
 *
 * GET  archeology/v1/objects             — полный список (как у attraction)
 * GET  archeology/v1/objects/{id}/adjacent — предыдущий/следующий
 * GET  archeology/v1/objects/{id}/nearby?max_distance={km} — ближайшие по координатам
 */

add_action('rest_api_init', function () {
    register_rest_route('archeology/v1', '/objects', [
        'methods' => 'GET',
        'callback' => 'get_archeology_objects',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('archeology/v1', '/objects/(?P<id>\d+)/adjacent', [
        'methods' => 'GET',
        'callback' => 'get_archeology_adjacent',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('archeology/v1', '/objects/(?P<id>\d+)/nearby', [
        'methods' => 'GET',
        'callback' => 'get_archeology_nearby',
        'permission_callback' => '__return_true',
        'args' => [
            'max_distance' => [
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param > 0;
                },
            ],
        ],
    ]);
});

function get_archeology_objects() {
    $args = [
        'post_type' => 'archeology',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);
    $objects = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $meta = get_post_meta($post_id);
            $thumbnail_id = get_post_thumbnail_id($post_id);
            $imgSrc = $meta['archeology_imgSrc'][0] ?? '';

            if (empty($imgSrc) && $thumbnail_id) {
                $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                update_post_meta($post_id, 'archeology_imgSrc', $imgSrc);
            }

            $coordinates = explode(', ', $meta['archeology_coord'][0] ?? '0, 0');

            $era_terms = wp_get_post_terms($post_id, 'archeology_era', ['fields' => 'slugs']);
            $type_terms = wp_get_post_terms($post_id, 'archeology_type', ['fields' => 'slugs']);

            $objects[] = [
                'id' => $post_id,
                'coordinates' => [
                    (float) ($coordinates[0] ?? 0),
                    (float) ($coordinates[1] ?? 0),
                ],
                'title' => get_the_title(),
                'place' => $meta['archeology_place'][0] ?? '',
                'imgSrc' => $imgSrc,
                'color' => $meta['archeology_color'][0] ?? '',
                'summarize' => '',
                'content' => '',
                'img' => $thumbnail_id,
                'era' => $era_terms[0] ?? '',
                'type' => $type_terms[0] ?? '',
            ];
        }
        wp_reset_postdata();
    }

    return array_reverse($objects);
}

function get_archeology_coordinates($post_id) {
    $raw = get_post_meta($post_id, 'archeology_coord', true);
    $parts = explode(', ', $raw ?: '0, 0');

    return [
        'lat' => (float) ($parts[0] ?? 0),
        'lng' => (float) ($parts[1] ?? 0),
    ];
}

function format_archeology_nav_item($post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $imgSrc = get_post_meta($post_id, 'archeology_imgSrc', true);

    if (empty($imgSrc) && $thumbnail_id) {
        $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
    }

    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'place' => get_post_meta($post_id, 'archeology_place', true),
        'imgSrc' => $imgSrc,
        'img' => $thumbnail_id,
    ];
}

function get_archeology_adjacent($request) {
    $id = intval($request['id']);

    $ids = get_posts([
        'post_type' => 'archeology',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);

    $position = array_search($id, $ids);
    $result = ['prev' => null, 'next' => null];

    if ($position !== false) {
        if ($position > 0) {
            $result['prev'] = format_archeology_nav_item($ids[$position - 1]);
        }
        if ($position < count($ids) - 1) {
            $result['next'] = format_archeology_nav_item($ids[$position + 1]);
        }
    }

    return rest_ensure_response($result);
}

function archeology_haversine_distance_km($lat1, $lng1, $lat2, $lng2) {
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function get_archeology_nearby($request) {
    $id = intval($request['id']);
    $post = get_post($id);

    if (!$post || $post->post_type !== 'archeology') {
        return new WP_Error('archeology_not_found', 'Памятник не найден', ['status' => 404]);
    }

    $max_distance = (float) $request->get_param('max_distance');
    $origin = get_archeology_coordinates($id);

    if ($origin['lat'] === 0.0 && $origin['lng'] === 0.0) {
        return rest_ensure_response(['nearby' => []]);
    }

    $candidate_ids = get_posts([
        'post_type' => 'archeology',
        'post_status' => 'publish',
        'post__not_in' => [$id],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $scored = [];
    foreach ($candidate_ids as $candidate_id) {
        $coord = get_archeology_coordinates($candidate_id);
        if ($coord['lat'] === 0.0 && $coord['lng'] === 0.0) continue;

        $distance = archeology_haversine_distance_km($origin['lat'], $origin['lng'], $coord['lat'], $coord['lng']);
        if ($distance <= $max_distance) {
            $scored[] = ['id' => $candidate_id, 'distance' => $distance];
        }
    }

    usort($scored, function ($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    $nearby = array_map(function ($item) {
        $data = format_archeology_nav_item($item['id']);
        $data['distanceKm'] = round($item['distance'], 3);
        return $data;
    }, array_slice($scored, 0, 4));

    return rest_ensure_response(['nearby' => $nearby]);
}

// ============================================================
// Заполнение тестовыми данными (однократное, по флагу в опциях)
// ============================================================

add_action('init', function () {
    if (get_option('archeology_data_seeded')) return;

    // Создаём таксономии
    $eras = [
        'mezolit' => 'Мезолит',
        'neolit' => 'Неолит',
        'bronze' => 'Эпоха бронзы',
        'iron' => 'Ранний железный век',
        'medieval' => 'Средневековье',
    ];
    foreach ($eras as $slug => $name) {
        if (!term_exists($slug, 'archeology_era')) {
            wp_insert_term($name, 'archeology_era', ['slug' => $slug]);
        }
    }

    $types = [
        'gorodishche' => 'Городище',
        'selishche' => 'Селище',
        'stoyanka' => 'Стоянка',
        'mogilnik' => 'Могильник',
        'klad' => 'Клад',
    ];
    foreach ($types as $slug => $name) {
        if (!term_exists($slug, 'archeology_type')) {
            wp_insert_term($name, 'archeology_type', ['slug' => $slug]);
        }
    }

    // Тестовые памятники (из моков ArcheologyView.vue)
    $sites = [
        [
            'title' => 'Шиханы-1',
            'content' => '<p>Многослойная стоянка эпохи мезолита на правом берегу реки Вятки. В ходе раскопок обнаружены кремнёвые орудия: скребки, резцы, ножи, а также микролитические пластины, характерные для мезолитической эпохи лесной полосы Восточной Европы.</p><p>Памятник открыт в 1972 году археологической экспедицией Кировского педагогического института. Исследования продолжались несколько полевых сезонов. Материалы стоянки позволили впервые для Вятского региона выделить мезолитический горизонт культурного слоя.</p>',
            'place' => 'Котельничский район, правый берег р. Вятки',
            'coord' => '58.310, 48.310',
            'era' => 'mezolit',
            'type' => 'stoyanka',
            'summarize' => 'Многослойная стоянка эпохи мезолита. Найдены кремнёвые орудия: скребки, резцы, ножи.',
        ],
        [
            'title' => 'Белая Локша',
            'content' => '<p>Неолитическая стоянка на берегу озера Белая Локша в Слободском районе. Характерна керамика с гребенчато-накольчатым орнаментом, типичным для неолитических культур лесной зоны.</p><p>Помимо керамики найдены каменные орудия: топоры, тёсла, наконечники стрел из кремня и сланца. Особый интерес представляют костяные изделия с резным орнаментом, свидетельствующие о развитом художественном вкусе древнего населения.</p>',
            'place' => 'Слободской район, озеро Белая Локша',
            'coord' => '58.720, 50.180',
            'era' => 'neolit',
            'type' => 'stoyanka',
            'summarize' => 'Неолитическая стоянка на берегу озера. Характерна керамика с гребенчато-накольчатым орнаментом.',
        ],
        [
            'title' => 'Ананьинское городище',
            'content' => '<p>Эталонный памятник ананьинской культуры (VIII–III вв. до н.э.), расположенный на высоком мысу правого берега реки Вятки в Малмыжском районе. Городище укреплено валом и рвом, общая площадь — около 2,5 га.</p><p>При раскопках найдены бронзовые кельты, наконечники копий, ножи, украшения, а также керамика с характерным шнуровым и гребенчатым орнаментом. Материалы Ананьинского городища стали эталонными для выделения ананьинской археологической культуры Прикамья.</p>',
            'place' => 'Малмыжский район, р. Вятка',
            'coord' => '56.520, 50.950',
            'era' => 'bronze',
            'type' => 'gorodishche',
            'summarize' => 'Эталонный памятник ананьинской культуры. Найдены бронзовые кельты, наконечники копий, керамика.',
        ],
        [
            'title' => 'Буйское городище',
            'content' => '<p>Укреплённое поселение ананьинской культуры на высоком мысу реки Чепцы в Кирово-Чепецком районе. Городище имеет сложную систему укреплений из двух линий валов и рвов, защищавших площадку поселения площадью около 1,5 га.</p><p>Культурный слой содержит остатки наземных и полуземляночных жилищ, очаги, хозяйственные ямы. Среди находок — бронзовые украшения, железные ножи, костяные орудия, керамика.</p>',
            'place' => 'Кирово-Чепецкий район, р. Чепца',
            'coord' => '58.560, 50.050',
            'era' => 'bronze',
            'type' => 'gorodishche',
            'summarize' => 'Укреплённое поселение ананьинской культуры на высоком мысу.',
        ],
        [
            'title' => 'Шиховское городище',
            'content' => '<p>Городище ананьинской культуры на высоком мысу правого берега Вятки в Оричевском районе. Площадка городища возвышается над рекой на 45 метров, укреплена валом высотой до 2,5 м и глубоким рвом.</p><p>Раскопки выявили остатки бревенчатых укреплений по краю площадки, несколько жилищ-полуземлянок и большое количество находок: бронзовые наконечники стрел, кельты, ножи, глиняные пряслица и грузила.</p>',
            'place' => 'Оричевский район, р. Вятка',
            'coord' => '58.380, 49.050',
            'era' => 'iron',
            'type' => 'gorodishche',
            'summarize' => 'Городище ананьинской культуры на высоком мысу с остатками вала и рва.',
        ],
        [
            'title' => 'Пижемское городище',
            'content' => '<p>Укреплённое поселение ананьинской культуры на останце коренного берега реки Пижмы в Советском районе. Памятник занимает стратегически выгодное положение при слиянии Пижмы и Вятки.</p><p>Культурный слой содержит материалы нескольких эпох — от бронзового века до средневековья, что делает городище уникальным стратиграфическим памятником для всего Вятского региона. Найдены предметы вооружения, орудия труда, украшения.</p>',
            'place' => 'Советский район, р. Пижма',
            'coord' => '57.580, 48.950',
            'era' => 'iron',
            'type' => 'gorodishche',
            'summarize' => 'Укреплённое поселение ананьинской культуры на останце коренного берега.',
        ],
        [
            'title' => 'Хлыновский кремль',
            'content' => '<p>Детинец средневекового Хлынова (XII–XV вв.) в историческом центре современного Кирова. Раскопки на территории кремля проводились в 1950-х, 1970-х и 2010-х годах.</p><p>Обнаружены остатки деревянных укреплений — городни, заполненные землёй и глиной, частокол по краю площадки. В культурном слое найдены предметы вооружения, быта, украшения, монеты, свидетельствующие об активной городской жизни средневекового Хлынова.</p>',
            'place' => 'г. Киров, исторический центр',
            'coord' => '58.603, 49.668',
            'era' => 'medieval',
            'type' => 'gorodishche',
            'summarize' => 'Детинец средневекового Хлынова (XII–XV вв.) с остатками деревянных укреплений.',
        ],
        [
            'title' => 'Котельничское городище',
            'content' => '<p>Остатки средневекового города Котельнича, известного с XII века. Городище расположено в центральной части современного города, на высоком берегу Вятки, и сохранило земляной вал высотой до 4 метров.</p><p>Археологические исследования выявили мощный культурный слой с остатками деревянных построек, уличных мостовых, ремесленных мастерских. Найдены многочисленные предметы вооружения, орудия труда, керамика, монеты русских княжеств.</p>',
            'place' => 'г. Котельнич, центральная часть',
            'coord' => '58.310, 48.340',
            'era' => 'medieval',
            'type' => 'gorodishche',
            'summarize' => 'Остатки средневекового города, известного с XII в., с земляным валом до 4 м.',
        ],
        [
            'title' => 'Вятский некрополь',
            'content' => '<p>Средневековый некрополь Хлынова в районе бывшего кремля, содержащий более 50 погребений XII–XVI веков. Раскопки некрополя велись с перерывами с 1950-х годов.</p><p>Погребальный обряд сочетает языческие и христианские черты, что характерно для периода христианизации Вятской земли. Среди находок — нательные кресты, украшения, предметы быта, положенные в могилы по обычаю.</p>',
            'place' => 'г. Киров, район бывшего кремля',
            'coord' => '58.602, 49.665',
            'era' => 'medieval',
            'type' => 'mogilnik',
            'summarize' => 'Средневековый некрополь Хлынова с более чем 50 погребениями.',
        ],
        [
            'title' => 'Клад серебряных монет',
            'content' => '<p>Клад из 136 серебряных монет XVI–XVII веков, обнаруженный в 1968 году при строительных работах на улице Московской в Кирове. Монеты были спрятаны в глиняном кувшине на глубине около 1 метра.</p><p>В составе клада — монеты Московского царства (копейки, денги, полушки) периода правления Ивана Грозного, Фёдора Иоанновича, Бориса Годунова и первых Романовых. Клад свидетельствует о торговых связях Хлынова с центральными районами Русского государства.</p>',
            'place' => 'г. Киров, ул. Московская',
            'coord' => '58.595, 49.660',
            'era' => 'medieval',
            'type' => 'klad',
            'summarize' => 'Клад из 136 серебряных монет XVI–XVII вв., обнаруженный в 1968 году.',
        ],
    ];

    foreach ($sites as $site) {
        $post_id = wp_insert_post([
            'post_type' => 'archeology',
            'post_status' => 'publish',
            'post_title' => $site['title'],
            'post_content' => $site['content'],
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'archeology_summarize', $site['summarize']);
            update_post_meta($post_id, 'archeology_place', $site['place']);
            update_post_meta($post_id, 'archeology_coord', $site['coord']);
            wp_set_object_terms($post_id, $site['era'], 'archeology_era');
            wp_set_object_terms($post_id, $site['type'], 'archeology_type');
        }
    }

    update_option('archeology_data_seeded', 1);
}, 20);
