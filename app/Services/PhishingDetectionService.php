<?php

namespace App\Services;

class PhishingDetectionService
{
    /**
     * Analyze a single email using Python Model (Wrapper for batch with 1 item)
     */
    public function analyze(array $email): array
    {
        // Wrapper for single item -> batch
        $results = $this->analyzeBatch([
            ['id' => 'temp', 'body' => $email['body'] ?? '']
        ]);
        
        return $results['temp'] ?? [
            'label' => 'unknown', 
            'score' => 0, 
            'reasons' => ['Analysis failed']
        ];
    }

    /**
     * Batch analyze using Python Model
     * @param array $items Array of ['id' => unique_key, 'body' => string]
     * @return array Keyed by 'id' => ['label' => ..., 'score' => ...]
     */
    public function analyzeBatch(array $items, string $type = 'email'): array
    {
        if (empty($items)) return [];

        $scriptPath = config('services.python.script');
        
        // Dynamic model selection
        $modelName = ($type === 'sms') ? 'sms_model.pkl' : 'email_model.pkl';
        $modelPath = config('services.python.model_dir') . "/{$modelName}"; 

        // Use file-based IPC
        $tempFile = storage_path('app/temp_ai_batch_' . uniqid() . '.json');
        
        // Ensure accurate JSON encoding
        $jsonData = json_encode($items, JSON_THROW_ON_ERROR);
        file_put_contents($tempFile, $jsonData);

        // Use environmental path, fallback to simple 'python' command (common on Linux VPS)
        $pythonPath = config('services.python.path');

        $process = new \Symfony\Component\Process\Process([
            $pythonPath, 
            $scriptPath, 
            '--model', 
            $modelPath,
            '--input-file',
            $tempFile
        ], null, [
            'SystemRoot' => getenv('SystemRoot'),
            'PATH' => getenv('PATH'),
            'TEMP' => getenv('TEMP'),
            'TMP' => getenv('TMP'),
            'PYTHONIOENCODING' => 'utf-8'
        ]);
        
        // TIMEOUT: Increase just in case loading libraries is slow
        $process->setTimeout(120);
        $process->run();

        // Cleanup
        if (file_exists($tempFile)) {
             @unlink($tempFile);
        }

        if (!$process->isSuccessful()) {
            \Log::error("Python Process Failed");
            \Log::error("Exit Code: " . $process->getExitCode());
            \Log::error("STDOUT: " . $process->getOutput());
            \Log::error("STDERR: " . $process->getErrorOutput());
            return [];
        }

        $output = $process->getOutput();
        $results = json_decode($output, true);

        if (!is_array($results)) {
            \Log::error("Python Output Invalid: " . $output);
            return [];
        }

        // Key by ID for easy lookup
        $keyedResults = [];
        foreach ($results as $res) {
            $keyedResults[$res['id']] = $res;
        }

        return $keyedResults;
    }
}
