function titlechanges(num){
    if(num == 1){
        document.getElementById("titlename").textContent = "Dashboard";

        document.getElementById("dashboard").style.display = "block";
        document.getElementById("rooms").style.display="none";
        document.getElementById("Booking").style.display ="none";
        document.getElementById("reports").style.display="none";


    }else if(num == 2){
        document.getElementById("titlename").textContent = "Room";


        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display="block";
        document.getElementById("Booking").style.display ="none";
        document.getElementById("reports").style.display="none";

    }else if(num == 3){
        document.getElementById("titlename").textContent = "Booking";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display="none";
        document.getElementById("Booking").style.display ="block";
        document.getElementById("reports").style.display="none";

    }else{
        document.getElementById("titlename").textContent = "Reports";

        document.getElementById("dashboard").style.display = "none";
        document.getElementById("rooms").style.display="none";
        document.getElementById("Booking").style.display ="none";
        document.getElementById("reports").style.display="block";

    }
}