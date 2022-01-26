/**
 * Represents the clear all caches icon
 */
import JQElement from './JQElement';

class ClearAllCacheIcon extends JQElement {
    constructor(element = jQuery('#wpe-clear-all-cache-icon')) {
        super(element);
    }
    setSuccessIcon() {
        if (this.element.length) {
            this.element.attr(
                'style',
                "content: url(\"data:image/svg+xml,%3Csvg width='50' height='50' viewBox='0 0 32 33' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect y='0.600098' width='32' height='32' rx='16' fill='%230ecad4'/%3E%3Cpath d='M21 12.7993L14.2 19.5993L11.4 16.7993L10 18.1993L14.2 22.3993L22.4 14.1993L21 12.7993Z' fill='white'/%3E%3C/svg%3E \");"
            );
        }
    }

    setErrorIcon() {
        if (this.element.length) {
            this.element.attr(
                'style',
                "content: url(\"data:image/svg+xml,%3Csvg width='32' height='33' viewBox='0 0 32 33' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M16 0.242615C12.8355 0.242615 9.74207 1.181 7.11088 2.9391C4.4797 4.6972 2.42894 7.19606 1.21793 10.1197C0.0069327 13.0433 -0.309921 16.2604 0.307443 19.3641C0.924806 22.4678 2.44866 25.3187 4.6863 27.5563C6.92394 29.794 9.77486 31.3178 12.8786 31.9352C15.9823 32.5525 19.1993 32.2357 22.1229 31.0247C25.0466 29.8137 27.5454 27.7629 29.3035 25.1317C31.0616 22.5005 32 19.4071 32 16.2426C31.9952 12.0006 30.308 7.93375 27.3084 4.93421C24.3089 1.93466 20.242 0.247414 16 0.242615ZM3.20001 16.2426C3.19796 13.8473 3.86862 11.4996 5.13558 9.46686C6.40255 7.4341 8.21491 5.79798 10.3662 4.74485C12.5176 3.69172 14.9214 3.26391 17.304 3.51013C19.6866 3.75635 21.9522 4.66672 23.8427 6.13755L5.89494 24.0853C4.14652 21.8451 3.19786 19.0843 3.20001 16.2426ZM16 29.0426C13.1592 29.0442 10.3995 28.0955 8.16 26.3477L26.1051 8.40261C27.5751 10.2931 28.4848 12.5584 28.7306 14.9406C28.9764 17.3228 28.5484 19.7261 27.4954 21.877C26.4424 24.0278 24.8066 25.8398 22.7743 27.1067C20.742 28.3735 18.3948 29.0443 16 29.0426Z' fill='%23D21B46'/%3E%3C/svg%3E%0A\");"
            );
        }
    }
}

export default ClearAllCacheIcon;
