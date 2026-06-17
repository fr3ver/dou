<?php
$mapWidgetSrc = sprintf(
    'https://yandex.ru/map-widget/v1/?ll=%s%%2C%s&z=%d&pt=%s%%2C%s%%2Cpm2rdm',
    MAP_LON,
    MAP_LAT,
    MAP_ZOOM,
    MAP_LON,
    MAP_LAT
);
$mapYandexUrl = sprintf(
    'https://yandex.ru/maps/?ll=%s%%2C%s&z=%d&pt=%s%%2C%s%%2Cpm2rdm',
    MAP_LON,
    MAP_LAT,
    MAP_ZOOM,
    MAP_LON,
    MAP_LAT
);
?>
<section id="contacts" class="map-contacts-section">
    <div class="map-contacts-map" aria-hidden="true">
        <iframe
            class="map-contacts-iframe"
            title="Карта — <?= htmlspecialchars(SITE_NAME) ?>"
            src="<?= htmlspecialchars($mapWidgetSrc) ?>"
            loading="lazy"
            allowfullscreen></iframe>
    </div>
    <div class="container map-contacts-overlay">
        <div class="map-contacts-card">
            <h2 class="map-contacts-title">Контактная информация:</h2>
            <ul class="list-unstyled map-contacts-list mb-0">
                <li class="map-contacts-item">
                    <span class="map-contacts-icon"><i class="bi bi-telephone-fill"></i></span>
                    <a href="tel:<?= SITE_PHONE_TEL ?>"><?= SITE_PHONE ?></a>
                </li>
                <li class="map-contacts-item">
                    <span class="map-contacts-icon"><i class="bi bi-envelope-fill"></i></span>
                    <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
                </li>
                <li class="map-contacts-item">
                    <span class="map-contacts-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span><?= SITE_ADDRESS ?></span>
                </li>
                <li class="map-contacts-item">
                    <span class="map-contacts-icon"><i class="bi bi-clock-fill"></i></span>
                    <span>Пн–Пт: 7:00 – 19:00</span>
                </li>
            </ul>
            <p class="map-contacts-external small mb-0 mt-3">
                <a href="<?= htmlspecialchars($mapYandexUrl) ?>" target="_blank" rel="noopener noreferrer">
                    Открыть в Яндекс Картах <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </p>
        </div>
    </div>
</section>
