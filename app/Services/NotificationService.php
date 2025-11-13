<?php

namespace App\Services;

use CodeIgniter\Email\Email;
use App\Models\UserModel;
use App\Models\BranchModel;

class NotificationService
{
    protected $email;
    protected $userModel;
    protected $branchModel;

    public function __construct()
    {
        $this->email = \Config\Services::email();
        $this->userModel = new UserModel();
        $this->branchModel = new BranchModel();
        
        // Ensure email config is loaded
        $emailConfig = config('Email');
        if ($emailConfig) {
            $this->email->initialize([
                'protocol' => $emailConfig->protocol,
                'SMTPHost' => $emailConfig->SMTPHost,
                'SMTPUser' => $emailConfig->SMTPUser,
                'SMTPPass' => $emailConfig->SMTPPass,
                'SMTPPort' => $emailConfig->SMTPPort,
                'SMTPCrypto' => $emailConfig->SMTPCrypto,
                'fromEmail' => $emailConfig->fromEmail,
                'fromName' => $emailConfig->fromName,
            ]);
        }
    }

    /**
     * Send low stock alert email
     */
    public function sendLowStockAlert($productName, $currentStock, $minStockLevel, $branchId, $productCode = '')
    {
        try {
            // Get branch manager and admin emails
            $recipients = $this->getAlertRecipients($branchId);
            
            if (empty($recipients)) {
                log_message('warning', 'No recipients found for low stock alert');
                return false;
            }

            $branch = $this->branchModel->find($branchId);
            $branchName = $branch ? $branch['branch_name'] : 'Unknown Branch';

            $subject = "⚠️ Low Stock Alert: {$productName}";
            $message = $this->buildLowStockEmail($productName, $currentStock, $minStockLevel, $branchName, $productCode);

            // Send to all recipients
            foreach ($recipients as $email) {
                $this->email->setTo($email);
                $this->email->setSubject($subject);
                $this->email->setMessage($message);
                
                if (!$this->email->send()) {
                    log_message('error', 'Failed to send low stock alert email to: ' . $email);
                } else {
                    log_message('info', 'Low stock alert email sent to: ' . $email);
                }
                
                $this->email->clear();
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error sending low stock alert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send critical stock alert email
     */
    public function sendCriticalStockAlert($productName, $currentStock, $reorderPoint, $branchId, $productCode = '')
    {
        try {
            $recipients = $this->getAlertRecipients($branchId, true); // Include central office admin
            
            if (empty($recipients)) {
                log_message('warning', 'No recipients found for critical stock alert');
                return false;
            }

            $branch = $this->branchModel->find($branchId);
            $branchName = $branch ? $branch['branch_name'] : 'Unknown Branch';

            $subject = "🚨 CRITICAL Stock Alert: {$productName}";
            $message = $this->buildCriticalStockEmail($productName, $currentStock, $reorderPoint, $branchName, $productCode);

            foreach ($recipients as $email) {
                $this->email->setTo($email);
                $this->email->setSubject($subject);
                $this->email->setMessage($message);
                
                if (!$this->email->send()) {
                    log_message('error', 'Failed to send critical stock alert email to: ' . $email);
                } else {
                    log_message('info', 'Critical stock alert email sent to: ' . $email);
                }
                
                $this->email->clear();
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error sending critical stock alert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send expiring items alert
     */
    public function sendExpiringItemsAlert($items, $branchId)
    {
        try {
            $recipients = $this->getAlertRecipients($branchId);
            
            if (empty($recipients) || empty($items)) {
                return false;
            }

            $branch = $this->branchModel->find($branchId);
            $branchName = $branch ? $branch['branch_name'] : 'Unknown Branch';

            $subject = "⏰ Expiring Items Alert - {$branchName}";
            $message = $this->buildExpiringItemsEmail($items, $branchName);

            foreach ($recipients as $email) {
                $this->email->setTo($email);
                $this->email->setSubject($subject);
                $this->email->setMessage($message);
                
                if (!$this->email->send()) {
                    log_message('error', 'Failed to send expiring items alert email to: ' . $email);
                }
                
                $this->email->clear();
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error sending expiring items alert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email recipients for alerts
     */
    private function getAlertRecipients($branchId, $includeAdmin = false)
    {
        $recipients = [];

        // Get branch manager
        $branchManager = $this->userModel->where('branch_id', $branchId)
                                         ->where('role', 'branch_manager')
                                         ->where('is_active', 1)
                                         ->first();
        if ($branchManager && !empty($branchManager['email'])) {
            $recipients[] = $branchManager['email'];
        }

        // Get central office admin if requested
        if ($includeAdmin) {
            $admin = $this->userModel->where('role', 'admin')
                                    ->where('is_active', 1)
                                    ->first();
            if ($admin && !empty($admin['email'])) {
                $recipients[] = $admin['email'];
            }
        }

        return array_unique($recipients);
    }

    /**
     * Build low stock email message
     */
    private function buildLowStockEmail($productName, $currentStock, $minStockLevel, $branchName, $productCode)
    {
        $message = "Low Stock Alert\n";
        $message .= "================\n\n";
        $message .= "Product: {$productName}\n";
        if ($productCode) {
            $message .= "Product Code: {$productCode}\n";
        }
        $message .= "Branch: {$branchName}\n";
        $message .= "Current Stock: {$currentStock}\n";
        $message .= "Minimum Stock Level: {$minStockLevel}\n\n";
        $message .= "Please consider placing a purchase order to replenish stock.\n\n";
        $message .= "This is an automated alert from ChakaNoks SCMS.\n";

        return $message;
    }

    /**
     * Build critical stock email message
     */
    private function buildCriticalStockEmail($productName, $currentStock, $reorderPoint, $branchName, $productCode)
    {
        $message = "CRITICAL STOCK ALERT\n";
        $message .= "====================\n\n";
        $message .= "Product: {$productName}\n";
        if ($productCode) {
            $message .= "Product Code: {$productCode}\n";
        }
        $message .= "Branch: {$branchName}\n";
        $message .= "Current Stock: {$currentStock}\n";
        $message .= "Reorder Point: {$reorderPoint}\n\n";
        $message .= "⚠️ IMMEDIATE ACTION REQUIRED ⚠️\n\n";
        $message .= "Stock has fallen below the reorder point. Please place a purchase order immediately.\n\n";
        $message .= "This is an automated alert from ChakaNoks SCMS.\n";

        return $message;
    }

    /**
     * Build expiring items email message
     */
    private function buildExpiringItemsEmail($items, $branchName)
    {
        $message = "Expiring Items Alert\n";
        $message .= "====================\n\n";
        $message .= "Branch: {$branchName}\n\n";
        $message .= "The following items are expiring soon:\n\n";

        foreach ($items as $item) {
            $days = isset($item['days_remaining']) ? $item['days_remaining'] : 'N/A';
            $message .= "- {$item['product_name']}";
            if (isset($item['product_code'])) {
                $message .= " ({$item['product_code']})";
            }
            $message .= "\n";
            $message .= "  Expiry Date: " . ($item['expiry_date'] ?? 'N/A') . "\n";
            $message .= "  Days Remaining: {$days}\n";
            if (isset($item['quantity'])) {
                $message .= "  Quantity: {$item['quantity']}\n";
            }
            $message .= "\n";
        }

        $message .= "\nPlease take appropriate action to use or transfer these items.\n\n";
        $message .= "This is an automated alert from ChakaNoks SCMS.\n";

        return $message;
    }
}

