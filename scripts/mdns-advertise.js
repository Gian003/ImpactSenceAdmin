// Advertises this dev machine's Laravel server via mDNS as "impactsense.local:8000".
//
// The ESP32 firmware (ImpactSense-Device/src/connectivity.cpp: resolveApiHost())
// and the mobile app (ImpactSense-Mobile/lib/core/services/api_client.dart) both
// resolve this hostname instead of a hardcoded LAN IP, so a DHCP-assigned IP
// change on this machine no longer breaks their connection to the backend.
//
// Uses bonjour-service (pure JS mDNS, no native bindings) instead of relying on
// an OS mDNS responder, so this works on Windows without installing Bonjour.
import { Bonjour } from 'bonjour-service';

const bonjour = new Bonjour();

const service = bonjour.publish({
  name: 'ImpactSense Backend',
  host: 'impactsense',
  type: 'http',
  port: 8000,
});

console.log('[mdns] Advertising "impactsense.local:8000". Press Ctrl+C to stop.');

process.on('SIGINT', () => {
  service.stop(() => {
    bonjour.destroy();
    process.exit(0);
  });
});
