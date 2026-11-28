<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        session()->forget('url.intended');
        $target = route('login');

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return new JsonResponse([
                'redirect' => $target,
            ], 200);
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }
}
