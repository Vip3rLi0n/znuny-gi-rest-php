<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';

use Unirest\Request;

Request::defaultHeader("Accept", "application/json");
Request::defaultHeader("Content-Type", "application/json");
Request::verifyPeer(true);

$FQDN = 'otrs.nizzy.xyz';
$WebServiceName = 'GenericTicketConnectorREST';
$BaseURL = "https://$FQDN/otrs/nph-genericinterface.pl/Webservice/$WebServiceName";
$headers = [];
$body = json_encode(
    [
        "UserLogin" => "wc",
        "Password"  => "wc",
    ]
);

/**
 * SessionCreate
 *
 * http://doc.otrs.com/doc/api/otrs/6.0/Perl/Kernel/GenericInterface/Operation/Session/SessionCreate.pm.html
 */
$response = Request::post($BaseURL."/Session", $headers, $body);
if (!$response->body->SessionID) {
    print "No SessionID returned \n";
    exit(1);
}
$SessionID = $response->body->SessionID;
print "Your SessionID is $SessionID\n";


/**
 * TicketCreate
 *
 * https://doc.otrs.com/doc/api/otrs/6.0/Perl/Kernel/GenericInterface/Operation/Ticket/TicketCreate.pm.html
 */
$attachment = file_get_contents("README.md");
$body = json_encode([
        'SessionID' => $SessionID,
        'Ticket' => [
            'Title' => 'Example ticket from PHP',
            'Queue' => 'Postmaster',
            'CustomerUser' => 'info@znuny.com',
            'State' => 'new',
            'Priority' => '3 normal',
            'OwnerID' => 1,
        ],
        'Article' =>[
            'CommunicationChannel' => 'Email',
            'ArticleTypeID' => 1,
            'SenderTypeID' => 1,
            'Subject' => 'Example',
            'Body' => 'This is a GenericInterface example.',
            'ContentType' => 'text/plain; charset=utf8',
            'Charset' => 'utf8',
            'MimeType' => 'text/plain',
            'From' => 'info@znuny.com',
        ],
        'Attachment' => [
            'Content' => base64_encode($attachment),
            'ContentType' => 'text/plain',
            'Filename' => 'README.md'
        ],
    ]
);

$response = Request::post($BaseURL."/Ticket", $headers, $body);
if ( $response->body->Error ) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "ErrorCode $ErrorCode\n";
    print "ErrorMessage $ErrorMessage\n";
}

$param = [
'SessionID' => $SessionID,
];
$response = Unirest\Request::get($BaseURL."/Ticket/${TicketID}", $headers, null, $param);
if ( $response->body->Error ) {
$ErrorCode = $response->body->Error->ErrorCode;
$ErrorMessage = $response->body->Error->ErrorMessage;
print "ErrorCode $ErrorCode\n";
print "ErrorMessage $ErrorMessage\n";
exit(1);
}
$ticket = $response->body->Ticket;

print "\nRetrieved Ticket\n";
print "Queue: ".$ticket->Queue."\n";
print "State: ".$ticket->State."\n";

/**
*
* SessionDestroy
*
*/
$param = [
'SessionID' => $SessionID,
];
$response = Unirest\Request::delete($BaseURL."/Session", $headers, $param);
if ( $response->body->Error ) {
$ErrorCode = $response->body->Error->ErrorCode;
$ErrorMessage = $response->body->Error->ErrorMessage;
print "ErrorCode $ErrorCode\n";
print "ErrorMessage $ErrorMessage\n";
exit(1);
}
print "\nSessionID $SessionID was destroyed\n";

?>
