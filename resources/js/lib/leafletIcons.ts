import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

let configured = false;

export function configureLeafletIcons() {
    if (configured) {
        return;
    }

    // Leaflet detects an imagePath from its CSS and prepends it to iconUrl.
    // Vite imports already return full dev/build URLs, so leave imagePath empty.
    L.Icon.Default.imagePath = '';
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    configured = true;
}
