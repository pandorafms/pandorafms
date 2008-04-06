int adcCount;
int shtClk=11;
int shtData=10;
int ioByte;
int ackBit;
double retVal; // Return value from SHT11
//--------//
int tC;      // Temperatura en ºC
int rhLin;        // Humedad
int rhTrue;      // humedad relatica con tC
//-------//
int dly;
int cmdByte;
int dataByte1, dataByte2, pin, valor,i;
uint8_t bitmask;
//para switch de puertas 1 ESTÁ CERRADA
int open_8=1;
int open_9=1;



void SHT_Write_Byte(void) {
   pinMode(shtData, OUTPUT);
   shiftOut(shtData, shtClk, MSBFIRST, ioByte);
   pinMode(shtData, INPUT);
   digitalWrite(shtData, LOW);
   digitalWrite(shtClk, LOW);
   digitalWrite(shtClk, HIGH);
   ackBit = digitalRead(shtData);
   digitalWrite(shtClk, LOW);
}

int shiftIn() {
   int cwt;
   cwt=0;
   bitmask=128;
   while (bitmask >= 1) {
     digitalWrite(shtClk, HIGH);
     cwt = cwt + bitmask * digitalRead(shtData);
     digitalWrite(shtClk, LOW);
     bitmask=bitmask/2;
   }
   return(cwt);
}
void SHT_Read_Byte(void) {
   ioByte = shiftIn();
   digitalWrite(shtData, ackBit);
   pinMode(shtData, OUTPUT);
   digitalWrite(shtClk, HIGH);
   digitalWrite(shtClk, LOW);
   pinMode(shtData, INPUT);
   digitalWrite(shtData, LOW);
}

void SHT_Connection_Reset(void) {
   shiftOut(shtData, shtClk, LSBFIRST, 255);
   shiftOut(shtData, shtClk, LSBFIRST, 255);
}

void SHT_Soft_Reset(void) {
   SHT_Connection_Reset();
   ioByte = 30;
   ackBit = 1;
   SHT_Write_Byte();
   delay(15);
}

void SHT_Wait(void) {
   delay(5);
   dly = 0;
   while (dly < 600) {
     if (digitalRead(shtData) == 0) dly=2600;
     delay(1);
     dly=dly+1;
   }
}

void SHT_Start(void) {
   digitalWrite(shtData, HIGH);
   pinMode(shtData, OUTPUT);
   digitalWrite(shtClk, HIGH);
   digitalWrite(shtData, LOW);
   digitalWrite(shtClk, LOW);
   digitalWrite(shtClk, HIGH);
   digitalWrite(shtData, HIGH);
   digitalWrite(shtClk, LOW);

}

void SHT_Measure(int vSvc) {
   SHT_Soft_Reset();
   SHT_Start();
   ioByte = vSvc;
   SHT_Write_Byte();
   SHT_Wait();
   ackBit = 0;
   SHT_Read_Byte();
   int msby;
   msby = ioByte;
   ackBit = 1;
   SHT_Read_Byte();
   retVal = msby;
   retVal = retVal * 0x100;
   retVal = retVal + ioByte;
   if (retVal <= 0) retVal = 1;
}

int SHT_Get_Status(void) {
   SHT_Soft_Reset();
   SHT_Start();
   ioByte = 7;
   SHT_Write_Byte();
   SHT_Wait();
   ackBit = 1;
   SHT_Read_Byte();
   return(ioByte);
}

void SHT_Heater(void) {
   SHT_Soft_Reset();
   SHT_Start();
   ioByte = 6;
   SHT_Write_Byte();
   ioByte = 4;
   SHT_Write_Byte();
   ackBit = 1;
   SHT_Read_Byte();
   delay(500);
   SHT_Soft_Reset();
   SHT_Start();
   ioByte = 6;
   SHT_Write_Byte();
   ioByte = 0;
   SHT_Write_Byte();
   ackBit = 1;
   SHT_Read_Byte();
}

//Write Temp or Humidity via serial 1->tem 2->humidity
void SHT_write(unsigned int temporal ){
   if (temporal==1){
     // SHT11 #1 Temperature
     SHT_Measure(3);

     retVal = retVal*0.01 - 40; //conversion a ºC
     tC = retVal;
     Serial.println(retVal, DEC);

   }
   if (temporal==2){

     // SHT11 #1 Humidity
     SHT_Measure(5);
     rhLin = (retVal * 0.0405) - (retVal * retVal * 0.0000028) - 4;
rhTrue = (tC - 25) * (retVal * 0.00008 + 0.01) + rhLin;
retVal = rhTrue;
Serial.println(retVal, DEC);




   }
}

void setup()
{

   for (i=1;i<14;i++){
     pinMode(i,OUTPUT);
   }
    pinMode(8,INPUT);
     pinMode(9,INPUT);

   digitalWrite(11, HIGH);
   //Recordar que el 10 y el 11 están reservados para SHT11

   Serial.begin(9600); // open serial
   SHT_Connection_Reset();
   //test de inicio
   digitalWrite(13, HIGH);
   delay(2000);
   digitalWrite(13, LOW);

}

void loop(){


   //EL pin 8 y 9 son para comprobar si está o no abieto el contacto
   //open_8 está a 0 y si se abre se pone a uno hasta que se hace una  
lectura del pin con el comando
   //D8 o D9
   //A la hora de codificar cada una de las salidas digitales
   //mediante el router, estableceremos como norma, codificar
   //el estado HIGH para el pin 2 como: "H02". Y para un estado
   //LOW: "L02". Para referirnos a las entradas analógicas,
   //lo indicaremos anteponiendo la A:  "A5".
   //Dado que la comunicación serie, se lleva a cabo, como la
   //propia palabra indica, mediante secuencia de bytes, entonces
   //tendremos que llevar un control de los "paquetes" de datos
   //para identificar correctamente los 3 bytes de una codificacion
   //del estilo "H02"
   //Los pines 10 y 11 digitales están reservados para el SHT11
   //Para leerlo sólo hay que poner AT, para temperatura y AH para  
humedad
   if (digitalRead(8)==0){
    open_8=0;

   }
   if (digitalRead(9)==0){
    open_9=0;

   }



   //Comprobamos si llega señal del router:
   if (Serial.available()){
     cmdByte=Serial.read();
     //En caso de que el primer byte sea una H o una L, nos
     //preparamos para decodificar el pin, leyendo, previamente
     //el número de dicho pin. Para ello transformamos su nº
     //a entero, restando al código ascii, 48
     if (cmdByte==72 || cmdByte==76) //H or L
     {
       while(! Serial.available()){
       }
       dataByte1=Serial.read();
       while(! Serial.available()){
       }
       dataByte2=Serial.read();

       pin=(dataByte1-48)*10+dataByte2-48;

       if(cmdByte==72){
         digitalWrite(pin,HIGH);
       }
       else{
         digitalWrite(pin,LOW);
       }
     }
     //En caso de que fuese A, entonces es un
     //puerto analógico

     if (cmdByte==65){
       //primero hacemos la lectura del pin en cuestión:
       while(! Serial.available()){
       }
       dataByte1=Serial.read();

       pin=dataByte1;
       switch (pin) {
       case 84://T
         {
           SHT_write(1);
           break;
         }
       case 72: //H
         {
           SHT_write(2);
           break;
         }
       default:
         {
           valor=analogRead(pin);

           Serial.println(valor,DEC);
         }

       }
     }
     //en caso de ser D es un tema de puertas
     if (cmdByte==68){
        //primero hacemos la lectura del pin en cuestión:
       while(! Serial.available()){
       }
       dataByte1=Serial.read();

       pin=dataByte1-48;
      //Si es una alarma es una A (llamada DA)
      if (pin==17){
           if(open_8==0) {
          Serial.println("D8");
         }
         else
         {
          Serial.println(0,DEC);
         }
        }
   //Si es la lectura de una de las puertas, la 8 o 9
           if(pin==8){
           Serial.println(open_8,DEC);

           open_8=1;
         }
         if(pin==9){
           Serial.println(open_9,DEC);
           open_9=1;
         }

     }

   }

}
