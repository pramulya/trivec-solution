<?php

namespace App\Http\Controllers;

use App\Services\PhishingDetectionService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    protected $detector;

    public function __construct(PhishingDetectionService $detector)
    {
        $this->detector = $detector;
    }

    public function inbox()
    {
        // 1. Define the "Fake" SMS Data (Backend Source)
        $messages = [
            ['id' => 'sms_1', 'from' => '+628123456789', 'text' => 'OTP Anda adalah 123456', 'time' => '10:21'],
            ['id' => 'sms_2', 'from' => 'Bank XYZ', 'text' => 'Transaksi Rp1.200.000 berhasil', 'time' => '09:10'],
            ['id' => 'sms_3', 'from' => 'Promo', 'text' => 'Diskon besar! Klik link sekarang http://bit.ly/scam', 'time' => 'Kemarin'],
            ['id' => 'sms_4', 'from' => 'Unknown', 'text' => 'You have won a lottery! Claim at http://phishing.com', 'time' => '2 days ago'],
            ['id' => 'sms_5', 'from' => 'Mom', 'text' => 'Please buy some eggs', 'time' => '3 days ago'],
        ];

        // 2. Format for AI (needs id and body)
        $aiInput = array_map(function($msg) {
            return ['id' => $msg['id'], 'body' => $msg['text']];
        }, $messages);

        // 3. Analyze using the SMS Model
        // We need to tell the service to use 'sms_model.pkl'. 
        // Currently analyzeBatch uses 'email_model.pkl' hardcoded.
        // We will update the Service to accept a model type.
        $results = $this->detector->analyzeBatch($aiInput, 'sms');

        // 4. Merge results back into messages
        foreach ($messages as &$msg) {
            if (isset($results[$msg['id']])) {
                $msg['analysis'] = $results[$msg['id']];
            }
        }

        return view('sms.inbox', compact('messages'));
    }
}
