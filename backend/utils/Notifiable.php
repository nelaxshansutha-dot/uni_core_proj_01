<?php
// Interface to enforce polymorphism
interface Notifiable {
    public function sendNotification($recipient, $message);
}
?>
