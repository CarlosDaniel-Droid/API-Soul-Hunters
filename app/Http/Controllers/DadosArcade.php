<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

class DadosArcade extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'usuario' => 'required|string|max:20',
            'pontos'  => 'required|integer',
        ]);

        // Lógica híbrida: Tenta variável de ambiente (Render), se não houver, tenta arquivo local
        $firebaseConfig = env('FIREBASE_CREDENTIALS');
        
        if ($firebaseConfig) {
            $serviceAccount = json_decode($firebaseConfig, true);
        } else {
            $path = storage_path('app/firebase.json');
            if (!file_exists($path)) {
                return response()->json(['error' => 'Credenciais do Firebase não encontradas.'], 500);
            }
            $serviceAccount = json_decode(file_get_contents($path), true);
        }

        // Gera token de acesso
        $accessToken = $this->gerarToken($serviceAccount);
        $projectId   = $serviceAccount['project_id'];

        // Monta a URL (Coleção Ordem, Documento com ID do usuário)
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Ordem/{$request->usuario}";

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($accessToken)->patch($url, [
            "fields" => [
                "usuario" => ["stringValue" => $request->usuario],
                "pontos"  => ["integerValue" => (string)$request->pontos],
            ]
        ]);

        return response()->json([
            'status'            => 'Dados enviados!',
            'firebase_response' => $response->json(),
        ]);
    }

    private function gerarToken(array $serviceAccount): string
    {
        $now = time();
        $payload = [
            "iss"   => $serviceAccount['client_email'], // Corrigido: usa o e-mail da conta
            "scope" => "https://www.googleapis.com/auth/datastore",
            "aud"   => "https://oauth2.googleapis.com/token",
            "exp"   => $now + 3600,
            "iat"   => $now,
        ];

        // Gera o JWT
        $jwt = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');

        // Troca o JWT por um access token
        /** @var \Illuminate\Http\Client\Response $tokenResponse */
        $tokenResponse = Http::asForm()->post("https://oauth2.googleapis.com/token", [
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion"  => $jwt,
        ]);

        return $tokenResponse->throw()->json()['access_token'];
    }
}
