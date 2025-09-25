<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\UserOutlet;
use App\Services\DeviceInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

    public function index()
    {
        return view('welcome');
    }

    public function getOutlet($user)
    {
        try {
            //  $conn = DB::connection('sqlsrv_eps');
            //  $sql = "SELECT DepotCode,DepotName FROM Depot";
            //  $pdo = $conn->getPdo()->prepare($sql);
            // $pdo->execute();
            // $rows = $pdo->fetchAll(\PDO::FETCH_ASSOC);
            $rows = UserOutlet::where('UserID', $user)->select('OutletCode')->pluck('OutletCode');
            if (count($rows) == 1 && $rows[0] == '*') {
                $rows = Depot::all();
            } else if (count($rows) == 0) {
                $rows = [];
            } else {
                $rows = Depot::whereIn('DepotCode', $rows)->get();
            }
            return response()->json(['outlet' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => "Oops! Something Went wrong"], 400);
        }
    }

    public function dashboardData()
    {
        $data = [];
        return $data;
    }
}
