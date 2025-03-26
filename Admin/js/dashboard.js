function titlechanges(num){
    if(num == 1){
        document.getElementById("titlename").textContent = "Dashboard";
    }else if(num == 2){
        document.getElementById("titlename").textContent = "Room";
    }else if(num == 3){
        document.getElementById("titlename").textContent = "Booking";
    }else{
        document.getElementById("titlename").textContent = "Reports";
    }
}