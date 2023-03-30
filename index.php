<?php
require_once "./db.php";

$connectDB = new ConnectDB;
$connectDB->connect();
$sql =  DB::table('MinhDuc')
// ->create('name','text')
// ->create('name1','text2')
// ->update('cot1', 'duc')
// ->update('cot12345', 'duc234')
->insert('cot12345', 'duc234')
->insert('duc', '123')
// ->join('bang1','abc.id','=','bang1.abc_id')
// ->orWhere('cot2','=','duc2')
// ->where('cot1','=','duc1')
// ->groupBy('cot1','cot2')
// ->having('cot2','=','duc2')
// ->having('cot4','=','duc3')
// ->orderBy('cot4','asc')
// ->orderBy('cot5','desc')
// ->limit(10)
->get();
$stmt = $connectDB->conn->prepare($sql);
$stmt->execute();
