<div class="ctf-cta-title">
    <div class="ctf-plugin-logo">
        <svg width="32" height="26" viewBox="0 0 32 26" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M31.6905 3.5C30.5355 4.025 29.2905 4.37 28.0005 4.535C29.3205 3.74 30.3405 2.48 30.8205 0.965C29.5755 1.715 28.1955 2.24 26.7405 2.54C25.5555 1.25 23.8905 0.5 22.0005 0.5C18.4755 0.5 15.5955 3.38 15.5955 6.935C15.5955 7.445 15.6555 7.94 15.7605 8.405C10.4205 8.135 5.66555 5.57 2.50055 1.685C1.94555 2.63 1.63055 3.74 1.63055 4.91C1.63055 7.145 2.75555 9.125 4.49555 10.25C3.43055 10.25 2.44055 9.95 1.57055 9.5V9.545C1.57055 12.665 3.79055 15.275 6.73055 15.86C5.78664 16.1183 4.79569 16.1543 3.83555 15.965C4.24296 17.2437 5.04085 18.3626 6.11707 19.1644C7.19329 19.9662 8.49372 20.4105 9.83555 20.435C7.56099 22.2357 4.74154 23.209 1.84055 23.195C1.33055 23.195 0.820547 23.165 0.310547 23.105C3.16055 24.935 6.55055 26 10.1805 26C22.0005 26 28.4955 16.19 28.4955 7.685C28.4955 7.4 28.4955 7.13 28.4805 6.845C29.7405 5.945 30.8205 4.805 31.6905 3.5Z" fill="#1B90EF"/>
        </svg>
    </div>
    <div class="ctf-plugin-title">
        <h3>{{genericText.getMoreFeatures}}</h3>
        <span class="ctf-cta-discount-label">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16.842 8.64984L9.34199 1.14984C9.02951 0.839904 8.60711 0.666153 8.16699 0.666504H2.33366C1.89163 0.666504 1.46771 0.842099 1.15515 1.15466C0.842587 1.46722 0.666993 1.89114 0.666993 2.33317V8.16651C0.666818 8.38643 0.710173 8.60422 0.794557 8.80731C0.878941 9.01041 1.00269 9.19479 1.15866 9.34984L8.65866 16.8498C8.97115 17.1598 9.39354 17.3335 9.83366 17.3332C10.275 17.3313 10.6975 17.1545 11.0087 16.8415L16.842 11.0082C17.155 10.697 17.3318 10.2745 17.3337 9.83317C17.3338 9.61325 17.2905 9.39546 17.2061 9.19237C17.1217 8.98927 16.998 8.80489 16.842 8.64984ZM9.83366 15.6665L2.33366 8.16651V2.33317H8.16699L15.667 9.83317L9.83366 15.6665ZM4.41699 3.1665C4.66422 3.1665 4.90589 3.23982 5.11146 3.37717C5.31702 3.51452 5.47723 3.70974 5.57184 3.93815C5.66645 4.16656 5.69121 4.41789 5.64297 4.66037C5.59474 4.90284 5.47569 5.12557 5.30088 5.30039C5.12606 5.4752 4.90333 5.59425 4.66086 5.64249C4.41838 5.69072 4.16705 5.66596 3.93864 5.57135C3.71023 5.47674 3.51501 5.31653 3.37766 5.11097C3.2403 4.90541 3.16699 4.66373 3.16699 4.4165C3.16699 4.08498 3.29869 3.76704 3.53311 3.53262C3.76753 3.2982 4.08547 3.1665 4.41699 3.1665Z" fill="#0068A0"/>
            </svg>
            {{genericText.liteFeedUsers}}
        </span>
    </div>
</div>
<div class="ctf-cta-boxes">
       <div class="ctf-cta-box">
            <span class="ctf-cta-box-icon" v-html="svgIcons.ctaBoxes.displayPhotos"></span>
            <span class="ctf-cta-box-title">{{genericText.ctadisplayPhotos}}</span>
        </div>
        <div class="ctf-cta-box">
            <span class="ctf-cta-box-icon" v-html="svgIcons.ctaBoxes.multiple"></span>
            <span class="ctf-cta-box-title">{{genericText.ctaMultiple}}</span>
        </div>
        <div class="ctf-cta-box">
            <span class="ctf-cta-box-icon" v-html="svgIcons.ctaBoxes.layouts"></span>
            <span class="ctf-cta-box-title">{{genericText.ctaLayouts}}</span>
        </div>
        <div class="ctf-cta-box">
            <span class="ctf-cta-box-icon" v-html="svgIcons.ctaBoxes.types"></span>
            <span class="ctf-cta-box-title">{{genericText.ctaTypes}}</span>
        </div>
</div>
<div class="ctf-cta-much-more">
    <div class="ctf-cta-mm-left">
        <h4>{{genericText.andMuchMore}}</h4>
    </div>
    <div class="ctf-cta-mm-right">
        <ul>
            <li v-for="item in genericText.ctfFreeCTAFeatures">{{item}}</li>
        </ul>
    </div>
</div>
<div class="ctf-cta-try-demo">
    <a :href="footerUpgradeUrl" class="ctf-btn-blue" target="_blank">
        {{genericText.tryDemo}}
        <span>
            <svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.8332 5L7.6582 6.175L11.4749 10L7.6582 13.825L8.8332 15L13.8332 10L8.8332 5Z" fill="white"/>
            </svg>
        </span>
    </a>
</div>