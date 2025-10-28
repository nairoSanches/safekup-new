<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'senha' => 'required|string',
        ]);

        $ldapHost = env('LDAP_HOST');
        $ldapPort = (int) env('LDAP_PORT', 389);
        $baseDn   = env('LDAP_DN'); // opcional

        $user = $request->input('login');
        $pass = $request->input('senha');

        $conn = @ldap_connect($ldapHost, $ldapPort);
        if (!$conn) {
            return back()->withErrors(['login' => 'Falha na conexão com o LDAP.']);
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($conn, $user, $pass)) {
            return back()->withErrors(['login' => 'Usuário ou senha inválidos.']);
        }

        Session::put('login', $user);
        @ldap_unbind($conn);
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }
}

