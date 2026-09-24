#include <SoftwareSerial.h>
#include <TinyGPS++.h>

int pinRxBuatan = D5;
int pinTxBuatan = D6;

SoftwareSerial gpsSerial(pinRxBuatan, pinTxBuatan);
TinyGPSPlus gps;

void setup() {
  pinMode(LED_BUILTIN, OUTPUT);
  Serial.begin(19200);      //Untuk Serial monitor
  gpsSerial.begin(9600);    //Untuk modul GPS
}

void loop() {
  while(gpsSerial.available() > 0){
    gps.encode(gpsSerial.read()); //Ambil data dari GPS dan encode
    if (gps.location.isUpdated()){
      Serial.print("Latitude\t= ");
      Serial.println(gps.location.lat(), 6);
      Serial.print("Longitude\t= ");
      Serial.println(gps.location.lng(), 6);
    }
  }
  delay(100);
}