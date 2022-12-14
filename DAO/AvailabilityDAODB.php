<?php
namespace DAO;

    use DAO\IAvailabilityDAO as IAvailabilityDAO;
    use \Exception as Exception;
    use DAO\Connection as Connection;
    use Models\Availability as Availability;
    use Models\Keeper as Keeper;
    use Models\Pet as Pet;
    use Models\Owner as Owner;
    use Models\Booking as Booking;

class AvailabilityDAODB implements IAvailabilityDAO{

    private $connection;
    private $tableName = "AvailabilityDate";

    public function GetAll()
    {
        try
        {
            $availabilityList = array();

            $query = "SELECT * FROM ".$this->tableName;

            $this->connection = Connection::GetInstance();

            $resultSet = $this->connection->Execute($query);
            
            foreach ($resultSet as $availability)
            {                
                        $availabilityNew=new Availability();
                        $availabilityNew->setAvailabilityId($availability['availabilityId']);
                        $availabilityNew->setKeeperId($availability['keeperId']);
                        $availabilityNew->setKeeperDate($availability['keeperDate']);
                        $availabilityNew->setAvailable($availability['available']);

                array_push($availabilityList, $availabilityNew);
            }

            return $availabilityList;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

    public function GetAllforKeeper($id){
        try{
            $allDates = $this->GetAll();// cada elemento del array que se guarda en $allDates es un Availability
            $keeper_dates = array();
            foreach ($allDates as $dates){
                if($dates->getKeeperId() == $id){
                    array_push($keeper_dates, $dates->getKeeperDate());
                }
            }
            return $keeper_dates;
        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function Add_AvailavilityDate($date, $id){
        try{ 
            $query = "INSERT INTO ". $this->tableName . " ( keeperId, keeperDate, available ) VALUES ( :keeperId, :keeperDate, :available );"; 

                $parameters["keeperId"] = $id;
                $parameters["keeperDate"] = $date;
                $parameters["available"] = true;

                $this->connection = Connection::GetInstance();

                $this->connection->ExecuteNonQuery($query, $parameters);

        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function Remove($id)
    {
        try{
            $query = "DELETE FROM ".$this->tableName." WHERE keeperId = :keeperId";
            $parameter["keeperId"] = $id;

            $this->connection = connection::GetInstance();
            $this->connection->ExecuteNonQuery($query,$parameter);

        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function Exist($id){
        try{

            $query = "SELECT * FROM ".$this->tableName." WHERE keeperId = :keeperId";
            $parameters["keeperId"] = $id;

            $this->connection = Connection::GetInstance();

            $result = $this->connection->Execute($query,$parameters);
            
            $availability = new Availability;

            if(isset($result[0])){
                $row = $result[0];

                $availability = $row;
                
            }else{
                $availability = null;
            }
            return $availability;
        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function GetFiltersDates($beginning, $end){
        try
        {
            $availabilityList = array();

            $query = "SELECT * FROM ".$this->tableName.' WHERE keeperDate >= :keeperDate && keeperDate <= :keeperDateend';
            $parameters["keeperDate"] = $beginning;
            $parameters["keeperDateend"] = $end;
           
            $this->connection = Connection::GetInstance();

            $resultSet = $this->connection->Execute($query, $parameters);
            
            foreach ($resultSet as $availability)
            {                
                        $availabilityNew=new Availability();
                        $availabilityNew->setAvailabilityId($availability['availabilityId']);
                        $availabilityNew->setKeeperId($availability['keeperId']);
                        $availabilityNew->setKeeperDate($availability['keeperDate']);
                        $availabilityNew->setAvailable($availability['available']);

                array_push($availabilityList, $availabilityNew);
            }

            return $availabilityList;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

    public function DatesAvailability($dates_list, $keeper_id){
        $allDates=$this->GetAll();
        foreach($allDates as $dates){
            foreach($dates_list as $list){
                if($dates->getKeeperDate()==$list && $dates->getKeeperId()==$keeper_id){
                    if($dates->getAvailable()==true){
                        
                    }else{
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function CancelAvailability(Booking $Booking){ // el foreach lo hace la consulta en la base de datos
        try{
            $query = "UPDATE ".$this->tableName." SET available = :available WHERE keeperId = ".$Booking->getKeeperId()->getUserId()." AND keeperDate BETWEEN "."'".$Booking->getStartDate()."'"." AND "."'".$Booking->getFinalDate()."'";

            $parameters["available"] = 0;
            
            $this->connection = Connection::GetInstance();
            $this->connection->ExecuteNonQuery($query, $parameters);
            
        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function IfExistReturnDates($id){
        try{

            $query = "SELECT * FROM ".$this->tableName." WHERE keeperId = ".$id;

            $this->connection = Connection::GetInstance();

            $resultSet = $this->connection->Execute($query);
            
            $availability_list = array();
            
            if($resultSet != null){
                foreach ($resultSet as $avDate)
                {                
                    $NewAvDate = new Availability;
                    $NewAvDate->setAvailabilityId($avDate['availabilityId']);
                    $NewAvDate->setKeeperId($avDate['keeperId']);
                    $NewAvDate->setKeeperDate($avDate['keeperDate']);
                    $NewAvDate->setAvailable($avDate['available']);

                    array_push($availability_list,$NewAvDate);
                }
                return $availability_list;
            }else{
                return null;
            }
        }catch(Exception $ex){
            throw $ex;
        }
    }

    public function GetFiltersDatesForKeeper(Booking $Booking){
        try
        {
            $availabilityList = array();

            $query = "SELECT * FROM ".$this->tableName.' WHERE keeperDate >= :keeperDate && keeperDate <= :keeperDateend AND keeperId = '.$Booking->getKeeperId()->getUserId();
            $parameters["keeperDate"] = $Booking->getStartDate();
            $parameters["keeperDateend"] = $Booking->getFinalDate();
           
            $this->connection = Connection::GetInstance();

            $resultSet = $this->connection->Execute($query, $parameters);
            
            foreach ($resultSet as $availability)
            {                
                        $availabilityNew=new Availability();
                        $availabilityNew->setAvailabilityId($availability['availabilityId']);
                        $availabilityNew->setKeeperId($availability['keeperId']);
                        $availabilityNew->setKeeperDate($availability['keeperDate']);
                        $availabilityNew->setAvailable($availability['available']);

                array_push($availabilityList, $availabilityNew);
            }

            return $availabilityList;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

}


?>