<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class ThemeProvider
{
    public function getTheme(Request $request): string
    {
        // Try to get from query parameter
        if ($request->query->has('theme')) {
            return $request->query->get('theme') === 'dark' ? 'dark' : 'light';
        }
        
        // Try to get from cookie
        if ($request->cookies->has('theme')) {
            return $request->cookies->get('theme') === 'dark' ? 'dark' : 'light';
        }
        
        // Default to light
        return 'light';
    }
}
