var Sounds = {
    playSound: function (value){
        var sound;
        if(value==1){
            sound = new Audio("chrome://pandorasidebar/content/sounds/aircraftalarm.wav");
        }
        if(value==2){
            sound = new Audio("chrome://pandorasidebar/content/sounds/air_shock_alarm.wav");
        }
        if(value==3){
            sound = new Audio("chrome://pandorasidebar/content/sounds/alien_alarm.wav");
        }
        if(value==4){
            sound = new Audio("chrome://pandorasidebar/content/sounds/alien_beacon.wav");
        }
        if(value==5){
            sound = new Audio("chrome://pandorasidebar/content/sounds/bell_school_ringing.wav");
        }
        if(value==6){
            sound = new Audio("chrome://pandorasidebar/content/sounds/Door_Alarm.wav");
        }
        if(value==7){
            sound = new Audio("chrome://pandorasidebar/content/sounds/EAS_beep.wav");
        }
        if(value==8){
            sound = new Audio("chrome://pandorasidebar/content/sounds/Firewarner.wav");
        }
        if(value==9){
            sound = new Audio("chrome://pandorasidebar/content/sounds/HardPCMAlarm.wav");
        }
        if(value==10){
            sound = new Audio("chrome://pandorasidebar/content/sounds/negativebeep.wav");
        }
        if(value==11){
            sound = new Audio("chrome://pandorasidebar/content/sounds/Star_Trek_emergency_simulation.wav");
        }
        if(value==0){
            sound = new Audio("chrome://pandorasidebar/content/sounds/aircraftalarm.wav");
        }
        sound.play();
    }
}