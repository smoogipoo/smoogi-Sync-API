<?php

class Response
{
    //Generic information errors
    const E_NOSERVICE 			= 0;
    const E_NOCREDENTIALS 		= 1;
    const E_INVALIDCRETENDTIALS = 2;
    const E_INVALIDTOKEN		= 3;
    const E_USEREXISTS			= 4;
    const E_INVALIDEMAIL		= 5;
    const E_EMPTYCREDENTIALS	= 6;
    //System errors
    const E_INVALIDREQUESTTYPE	= -2;
    const E_NORETURN			= -1;
    //Success responses
    const R_USERACCOUNTCREATED	= 400;
    const R_TOKENCALLBACK		= 401;
    const R_LOGOUTSUCCESS		= 402;

}
?>