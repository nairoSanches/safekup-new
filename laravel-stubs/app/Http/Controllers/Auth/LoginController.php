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

        $ldapHost    = env('LDAP_HOST');
        $ldapPort    = (int) env('LDAP_PORT', 389);
        $baseDn      = env('LDAP_DN');
        $netbios     = env('LDAP_NETBIOS', '');
        $upnSuffix   = env('LDAP_UPN_SUFFIX', '');
        $useStartTls = filter_var(env('LDAP_STARTTLS', false), FILTER_VALIDATE_BOOLEAN);

        $user = $request->input('login');
        $pass = $request->input('senha');

        $conn = @ldap_connect($ldapHost, $ldapPort);
        if (!$conn) {
            return back()->withErrors(['login' => 'Falha na conexão com o LDAP.']);
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        if ($useStartTls) { @ldap_start_tls($conn); }

        $candidates = [];
        $hasDomain = str_contains($user, '@') || str_contains($user, '\\');
        if ($hasDomain) {
            $candidates[] = $user;
        } else {
            if (!empty($upnSuffix)) {
                $suffix = str_starts_with($upnSuffix, '@') ? $upnSuffix : ('@' . $upnSuffix);
                $candidates[] = $user . $suffix;
            }
            if (!empty($netbios)) {
                $candidates[] = $netbios . '\\' . $user;
            }
            $candidates[] = $user; // fallback
        }

        $ok = false;
        foreach ($candidates as $rdn) {
            if (@ldap_bind($conn, $rdn, $pass)) { $ok = true; break; }
        }

        if (!$ok) {
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
