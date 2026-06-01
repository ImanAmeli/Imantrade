<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('admin');
        }
        View::render('admin/login', ['error' => flash('error')], null);
    }

    public function login(): void
    {
        if (!csrf_check()) {
            flash('error', 'نشست منقضی شده.');
            redirect('admin/login');
        }
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if (Auth::attempt($email, $pass)) {
            redirect('admin');
        }
        flash('error', 'ایمیل یا گذرواژه نادرست است.');
        redirect('admin/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('admin/login');
    }
}
