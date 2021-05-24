<?php

use Illuminate\Http\Request;

function userId(Request $request)
{
    if(filter_var($request->user_id, FILTER_VALIDATE_EMAIL))
        return 'email';
    else
        return 'username';
}

