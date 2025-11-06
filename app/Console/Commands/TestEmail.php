<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Testing email configuration...');
        $this->info('SMTP Host: ' . config('mail.mailers.smtp.host'));
        $this->info('SMTP Port: ' . config('mail.mailers.smtp.port'));
        $this->info('From Address: ' . config('mail.from.address'));
        $this->info('');

        try {
            Mail::raw('This is a test email from your Laravel application.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email');
            });

            $this->info('✓ Email sent successfully to ' . $email);
            $this->info('Check your inbox (and spam folder)');

        } catch (\Exception $e) {
            $this->error('✗ Failed to send email');
            $this->error('Error: ' . $e->getMessage());
            $this->error('');
            $this->error('Possible issues:');
            $this->error('1. Gmail App Password not configured (use App Password, not regular password)');
            $this->error('2. SMTP port blocked on Railway');
            $this->error('3. Invalid SMTP credentials');
        }
    }
}
