<?php
    namespace Controllers;

    use Helper\Validation as Validation;
    use DAO\BookingDAODB as BookingDAODB;
    use DAO\CouponDAODB as CouponDAODB;
    use DAO\PetDAODB as PetDAODB;
    use DAO\BankDAODB as BankDAODB;
    use DAO\AvailabilityDAODB as AvailabilityDAODB;
    use Models\Booking as Booking;
    use \Exception as Exception;
    use Controllers\MailController as MailController;
    use Controllers\ChatController as ChatController;

    class BookingController
    {   
        private $BookingDAO;
        private $CouponDAO;
        private $DataPets;
        private $BankDAO;
        private $AvailablilityDAO;
        private $KeeperController;
        private $OwnerController;
        private $MailController;

        public function __construct(){
            $this->BookingDAO = new BookingDAODB;
            $this->CouponDAO = new CouponDAODB;
            $this->DataPets=new PetDAODB;
            $this->BankDAO = new BankDAODB;
            $this->AvailablilityDAO = new AvailabilityDAODB;
            $this->KeeperController = new KeeperController;
            $this->OwnerController = new OwnerController;
            $this->MailController = new MailController;
            $this->ChatController = new ChatController;
        }

        public function MyBookings(){
            Validation::ValidUser();
            try{
                $booking_list = $this->BookingDAO->GetOneBooking($_SESSION['loggedUser']->getUserId());
                $coupon_list = $this->CouponDAO->GetAll();
                require_once(VIEWS_PATH."keeper-bookings.php");
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function ShowListReservas(){
            Validation::ValidUser();
            //PASAR lista de pets
            try{
                $petsofowner=$this->DataPets->GetAllforOwner($_SESSION['loggedUser']->getUserId());
                $Booking_list=$this->BookingDAO->GetAllforOwner($petsofowner);
                //aca va un get for bookings, pero estaba cansado si me acuerdo lo hago 
                $coupons_list=$this->CouponDAO->GetAll();
                require_once(VIEWS_PATH."owner-reservations.php");
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function PayBooking($idbooking){
            //ultimo requisito de la logica
            Validation::ValidUser();
            //busco el keeper
            try{
                if($this->chekTypeFileALL('voucher')==true){
                $bookingselect= $this->BookingDAO->GetOnlyOneBooking($idbooking);
                $couponselect= $this->CouponDAO->GetOnlyOneCoupon($idbooking);
                //le agrego la plata en su banco
                var_dump($bookingselect->getKeeperId());
                $this->BankDAO->ModifyTotal($couponselect->getFullPayment()/2, $bookingselect->getKeeperId()->getBankKeeper());
                
                //cambia el estado de la reserva a super confirmada
                if($couponselect->getPaidAlready()==0){
                    $this->CouponDAO->Modify($idbooking, $couponselect->getFullPayment()/2, $this->RedirectImage('voucher'));
                    $this->BookingDAO->ConfirmationBooking($bookingselect);
                }else{
                    $this->CouponDAO->Modify2($idbooking, $couponselect->getFullPayment()/2, $this->RedirectImage('voucher'));
                    $this->BookingDAO->ConfirmationBookingTotalpay($bookingselect);
                }

                header('location:'.FRONT_ROOT.'Booking/ShowListReservas');
            } else{
                echo "<script> confirm('Formato/s de imagenes cargados inavlidos solo se permite: PNG, JPG y PDF');</script>";
                $this->ShowListReservas();
            }
            }catch(Exception $ex)
            {
                echo $ex;
                require_once(VIEWS_PATH."error-page.php");
            }
        }



        public function Action($action){
            Validation::ValidUser();
            try{
                $actionSepared = explode(",",$action);
                $Booking = new Booking;
                $Booking = $this->BookingDAO->GetOnlyOneBooking($action[0]);
                
                $messageDrop = 0;
                
                if($actionSepared[1] == "Approve"){

                    $this->BookingDAO->ApproveBooking($Booking);

                    $date1 = date_create($Booking->getStartDate());
                    $date2 = date_create($Booking->getFinalDate());
                    $diff = $date1->diff($date2);

                    $precioTotal = $_SESSION['loggedUser']->getPrice() * ($diff->days+1);
                    $this->CouponDAO->Add_Coupon($precioTotal,$Booking->getIdBooking());

                    $keeperSessNonActiveBList = $this->BookingDAO->BookingsConfirmationPendient($_SESSION['loggedUser']->getUserId());
                    
                    $this->AvailablilityDAO->CancelAvailability($Booking);

                    foreach($keeperSessNonActiveBList as $oneBooking){
                        if($this->AutoCancel($oneBooking) == 1){
                            $messageDrop = 1;
                            $this->BookingDAO->RejectBooking($oneBooking);
                            $this->MailController->sendRejectBooking($oneBooking);
                        }
                    }

                    if($messageDrop == 1){
                        echo "<script> confirm('Alguna de las reservas que tiene pendiente coincide con el rango de fechas de la reserva que esta aceptando, automaticamente se rechazaran todas las que cumplan esa condicion. Chequee el apartado de MIS RESERVAS para corroborar la informacion!');</script>";
                    }
                    $this->MailController->sendConfirmationBooking($Booking);
                    $this->KeeperController->ShowHome();

                }else{
                    $this->BookingDAO->RejectBooking($Booking);
                    $this->MailController->sendRejectBooking($Booking);
                    $this->KeeperController->ShowHome();
                }
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function NewBooking($first_date, $end_date, $id_mascot, $id_keeper){
            Validation::ValidUser();
            try{
                if($id_keeper=='chat'){

                }else{
                if($first_date > $end_date){
                    echo "<script> confirm('La fecha de inicio debe ser anterior a la fecha final... Vuelva a intentar');</script>";
                    $this->OwnerController->ShowHome();
                }else{
    
                    $first_date2 = strtotime($first_date);
                    $end_date2 = strtotime($end_date);
        
                    $day = 86400; //24 horas * 60 minutos x hora * 60 segundos x minuto (24*60*60)=86400 
                    $dates = array();
                    for($i = $first_date2; $i <= $end_date2; $i += $day){
                        $dateToAdd = date("Y-m-d", $i);
                        array_push($dates,$dateToAdd);
                    }
                    
                    if($this->AvailablilityDAO->DatesAvailability($dates, $id_keeper)==true){
                        $bookininProgres=new Booking;
                        $bookininProgres->setPetId($id_mascot);
                        $bookininProgres->setStartDate($first_date);
                        $bookininProgres->setFinalDate($end_date);
                        $bookininProgres->setKeeperId($id_keeper);
                        // la vamos a usar coupon $bookininProgres->setTotalValue(count($dates)* $this->DataKeepers->getKeeper($id_keeper)->getPrice());
                    
                        //falta hacer el bookingdao   
                        $this->BookingDAO->Add($bookininProgres);
                        
                        echo "<script> confirm('Reserva Creada con exito!! Una vez confirmada por el Keeper sera notificado');</script>";
                        $this->OwnerController->ShowHome();
                    }else{
                        echo "<script> confirm('El rango de fechas seleccionado no es valido');</script>";
                        $this->OwnerController->ShowHome();
                    }
                }
                }
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function DeleteBooking($id_booking){
            Validation::ValidUser();
            try{
                $this->BookingDAO->Remove($id_booking);
                $this->KeeperController->ShowHome();
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function ReviewBooking($booking){
            Validation::ValidUser();

            try{
                $Booking = $this->BookingDAO->ConfirmReview($booking);
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
            
        }

        private function AutoCancel(Booking $Booking){
            try{
                // si al menos una de las fechas de la reserva esta como 0 en availabilitydate, la cancelo
                $availableDates = $this->AvailablilityDAO->GetFiltersDatesForKeeper($Booking);
                $autoCancel=0;
                foreach($availableDates as $aDates){
                    if($aDates->getAvailable() == 0){
                        $autoCancel = 1;
                    }
                }
                return $autoCancel;
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        
        }

        private function chekTypeFileALL($filename){
            $ok=false;
            if($_FILES[$filename]["type"]=='image/jpeg' || $_FILES[$filename]["type"]=='image/png' || $_FILES[$filename]["type"]=='application/pdf' || $_FILES[$filename]["type"]=='image/jpg'){
                $ok=true;
            }
            return $ok;
        }

        private function RedirectImage($filename){
            $base_name= basename($_FILES[$filename]["name"]);
            $final_name = date("m-d-y")."-".date("H-i-s")."-".$base_name;
            $route= VIEWS_PATH."Styles/imgVouchers/".$final_name;
            move_uploaded_file($_FILES[$filename]['tmp_name'], $route);

            return $route;
        }
        
    }
?>