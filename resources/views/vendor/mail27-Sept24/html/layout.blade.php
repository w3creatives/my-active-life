<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
</head>
<body>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f3f3f3;min-width:350px;font-size:1px;line-height:normal">
            <tbody>
                <tr>
                <td align="center" valign="top">
                    
                    <table cellpadding="0" cellspacing="0" border="0" width="750" style="width:100%;max-width:750px;min-width:350px;background:#f3f3f3">
                        <tbody><tr>
                            <td width="3%" style="width:3%;max-width:3%;min-width:10px">&nbsp;</td>
                            <td align="center" valign="top" style="background:#ffffff">

                               {{ $header ?? '' }}

                                <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width:88%!important;min-width:88%;max-width:88%">
                                    <tbody><tr>
                                        <td align="left" valign="top">
                                            <font face="'Source Sans Pro', sans-serif" color="#1a1a1a" style="font-size:48px;line-height:50px;font-weight:300;letter-spacing:-1.5px">
                                                <span style="font-family:'Source Sans Pro',Arial,Tahoma,Geneva,sans-serif;color:#1a1a1a;font-size:44px;line-height:46px;font-weight:300;letter-spacing:-1.5px">
                                                    {{ $heading ?? '' }}
                                                    </span>
                                            </font>
                                          {{ $heading ?? '' }}
                                            <div style="height:30px;line-height:30px;font-size:28px">&nbsp;</div>
                                            <table cellpadding="0" cellspacing="0" border="0" width="270" style="width:270px!important;max-width:270px;min-width:270px;background:#27cbcc;border-radius:4px">
                                                <tbody><tr>
                                                    <td align="center" valign="middle" height="55">
                                                        {{ Illuminate\Mail\Markdown::parse($slot) }}
                                                    </td>
                                                </tr>
                                            </tbody></table>
                                           

                                            <div style="height:90px;line-height:90px;font-size:88px">&nbsp;</div>
                                        </td>
                                    </tr>
                                </tbody></table>

{{ $subcopy ?? '' }}


{{ $footer ?? '' }}
                            </td>
                            <td width="3%" style="width:3%;max-width:3%;min-width:10px">&nbsp;</td>
                        </tr>
                    </tbody>
                    </table>
                    
                </td>
            </tr>
        </tbody></table>
</body>
</html>
