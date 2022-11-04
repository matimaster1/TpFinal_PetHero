<?php
    namespace DAO;

    use Models\Booking as Booking;
    use Models\Pet as Pet;

    interface IBookingDAODB
    {
        function GetAll();
        function Add(Booking $newBooking);
        function Remove($id);
        function GetAllforKeeper($id);
        function GetAllforOwner($pets);
    }
?>