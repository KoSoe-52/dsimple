<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ThreedList;
use App\Models\ThreedTerminateNumber;
use App\Models\ThreedLuckyRecord;
class ThreedModeratorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $twod100 = ThreedList::all();
        $threedlists = $this->remaining($twod100);
        return view("threed.create",compact("threedlists"));
    }
    public function remaining($array=array())
    {
        date_default_timezone_set("Asia/Yangon");
        $hourMinute = date("Hi");
        //get Day
        $day = date("d");
        //break Time collect
        $breakTime="";
        if($day >=2 && $day <=16)
        {
            //ထွက်မည့်ရက် ၃ နာရီဆိုပိတ်မည်
            if($day == 16 && $hourMinute >=1500)
            {
                $breakTime ="breakTime";
            }
            $Ym = date("Y-m-");
            $date = $Ym."16";
        }else if($day >=17 && $day<=31)
        {
            //next month
            $currentDate = strtotime(date('Y-m-d'));
            $Ym = date("Y-m-", strtotime("+1 month", $currentDate));
            $date = $Ym."01";
        }else
        {
            // day 1 
            //ထွက်မည့်ရက် ၃ နာရီဆိုပိတ်မည်
            if($day == "01" && $hourMinute >=1500)
            {
                $breakTime ="breakTime";
            }
            $Ym = date("Y-m-");
            $date = $Ym."01";
        }
        if($breakTime == "breakTime")
        {
             /*
            * loop 1000
            * rest time
            * status 1 is in active
            */ 
            $amountOfNumber = array();
            foreach($array as $key=>$data)
            {
                $amountOfNumber[]=array("number"=>$data->number,"remaining"=>"breaktime","status"=>1);
            }
            return $amountOfNumber;
        }else
        {
            /*
            * loop 100
            */ 
            $amountOfNumber = array();
            foreach($array as $key=>$data)
            {
                $results = DB::select( DB::raw("SELECT SUM(price) as amount FROM threed_lucky_records 
                    WHERE number = :number AND date=:date  AND user_id=:user_id"), 
                    array(
                        'number' => $data->number,
                        'date'   => $date,
                        'user_id' => Auth::user()->id
                    )
                );
                $status = $this->breakNumbers($date,$data->number);
                $remainingAmount = Auth::user()->break - $results[0]->amount;
                $amountOfNumber[]=array("number"=>$data->number,"remaining"=>$remainingAmount,"status"=>0);
            }
            return $amountOfNumber;
        }
    }
    public function breakNumbers($date,$number)
    {
        $count = ThreedTerminateNumber::whereDate("date",$date)
                ->where("number",$number)
                ->where("branch_id",Auth::user()->branch_id)
                ->count();
        if($count > 0)
        {
            return 1;
        }else
        {
            return 0;
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("moderator.twodoption");
    }
    public function store(Request $request)
    {
        $validate = Validator::make($request->only('number','amount'), [
            'number' => 'required',
            'amount' => 'required',
            //'name'   => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => false,
                "data"   => "Amount and Number သတ်မှတ်ပါ"
            ]);
        } else {
            date_default_timezone_set("Asia/Yangon");
            $hourMinute = date("Hi");
            //get Day
            $day = date("d");
            //break Time collect
            $breakTime="";
            if($day >=2 && $day <=16)
            {
                //ထွက်မည့်ရက် ၃ နာရီဆိုပိတ်မည်
                if($day == 16 && $hourMinute >=1500)
                {
                    $breakTime ="breakTime";
                }
                $Ym = date("Y-m-");
                $date = $Ym."16";
            }else if($day >=17 && $day<=31)
            {
                //next month
                $currentDate = strtotime(date('Y-m-d'));
                $Ym = date("Y-m-", strtotime("+1 month", $currentDate));
                $date = $Ym."01";
            }else
            {
                // day 1 
                //ထွက်မည့်ရက် ၃ နာရီဆိုပိတ်မည်
                if($day == "01" && $hourMinute >=1500)
                {
                    $breakTime ="breakTime";
                }
                $Ym = date("Y-m-");
                $date = $Ym."01";
            }
            if($breakTime == "breakTime")
            {
                return response()->json([
                    "status" => false,
                    "data"   => "ခဏရပ်နားထားပါသည်"
                ]);
            }else
            {
                $vouncher_id = $this->getVouncherId();
               foreach($request->number as $key=>$number)
                {
                    ThreedLuckyRecord::create([
                        "name"   => $request->get("name"),
                        "date"   => $date,
                        "number" => $request->get("number")[$key],
                        "price" => $request->get("amount")[$key],
                        "user_id" => Auth::user()->id,
                        "vouncher_id" => $vouncher_id,
                        "inser_date_time" => date("Y-m-d H:i:s")
                    ]);
                }
                return response()->json([
                    "status" => true,
                    "data"   => $vouncher_id
                ],200);
            }
        }
    }
    public function getVouncherId()
    {
        $vouncher = ThreedLuckyRecord::latest()->first();
        if(!empty($vouncher))
        {
            return $vouncher->vouncher_id + 1;
        }else
        {
            return 1;
        }
    }
    public function history(Request $request)
    {
        date_default_timezone_set("Asia/Yangon");
        //get Day
        $day = date("d");
        if($day >=2 && $day <=16)
        {
            $Ym = date("Y-m-");
            $date = $Ym."16";
        }else if($day >=17 && $day<=31)
        {
            //next month
            $currentDate = strtotime(date('Y-m-d'));
            $Ym = date("Y-m-", strtotime("+1 month", $currentDate));
            $date = $Ym."01";
        }else
        {
            // day 1 
            $Ym = date("Y-m-");
            $date = $Ym."01";
        }
        //return $date;
        $histories = ThreedLuckyRecord::select("name","date","vouncher_id")
                    ->where("user_id",Auth::user()->id)
                    ->whereDate("date",$date)
                    ->groupBy("vouncher_id","date","name")
                    ->orderBy("vouncher_id","DESC");
        $countArray = array();
        if(!empty($request->get("date")))
        {
            $countArray[] =$histories->whereDate("threed_lucky_records.date","=",$request->get("date"));
        }
        if (count($countArray) > 0) {
            $histories = $histories->get();
        }else
        {
            $histories = $histories->whereDate("threed_lucky_records.date","=",$date)->get();
        }
        return view("moderator.3dhistory",compact("histories"));
    }
    /*
    * vouncher id
    */
    public function vouncher($id)
    {
        $vounchers = ThreedLuckyRecord::where("vouncher_id",$id)
                   ->where("user_id",Auth::user()->id)
                   ->get();
        return view("moderator.vouncher",compact("vounchers"));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
