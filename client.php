<?php
declare(strict_types=1);
require_once 'vendor/autoload.php';

use Unirest\Request;

Request::defaultHeader("Accept", "application/json");
Request::defaultHeader("Content-Type", "application/json");
Request::verifyPeer(true);

$FQDN = 'FIXME';
$WebServiceName = 'GenericTicketConnectorREST';
$BaseURL = "https://$FQDN/otrs/nph-genericinterface.pl/Webservice/$WebServiceName";
$headers = [];
$body = json_encode(
    [
        "UserLogin" => "FIXME",
        "Password"  => "FIXME",
    ]
);



/**
 * SessionCreate (Create a session)
 *
 * http://doc.otrs.com/doc/api/otrs/6.0/Perl/Kernel/GenericInterface/Operation/Session/SessionCreate.pm.html
 */
$response = Request::post($BaseURL."/Session", $headers, $body);
if (!$response->body||!property_exists($response->body,'SessionID')) {
    print "No SessionID were received. \n";
    exit(1);
}
$SessionID = $response->body->SessionID;
print "\nNotice: \n";
print "SessionID obtained. Your SessionID is $SessionID\n";



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
            'Queue' => 'Misc',
            'CustomerUser' => 'customer@test.com',
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
            'From' => 'root@localhost',
        ],
        'Attachment' => [
            'Content' => base64_encode($attachment),
            'ContentType' => 'text/plain',
            'Filename' => 'README.md'
        ],
    ]
);

$response = Request::post($BaseURL."/Ticket", $headers, $body);
if ($response->body && property_exists($response->body, 'Error')) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "\n\n";
    print "ErrorCode $ErrorCode\n\n";
    print "ErrorMessage $ErrorMessage\n\n";
    print "\n\n";
    exit(1);
}
$TicketNumber = $response->body->TicketNumber;
$TicketID = $response->body->TicketID;
$ArticleID = $response->body->ArticleID;
print "\nNotice: \n";
print "\nThe ticket $TicketNumber was created. Check it via https://$FQDN/otrs/index.pl?Action=AgentTicketZoom;TicketID=$TicketID\n\n";



/**
*
* TicketUpdate (Moving ticket to another queue, and also state of the ticket)
*
**/
$param = json_encode([
        'SessionID' => $SessionID,
        'Ticket' => [
                'Queue' => 'Warehouse',
                'State' => 'new'
        ]
]);
$response = Unirest\Request::patch($BaseURL."/Ticket/${TicketID}", $headers, null, $param);
if ($response->body && property_exists($response->body, 'Error')) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "\n\n";
    print "ErrorCode $ErrorCode\n\n";
    print "ErrorMessage $ErrorMessage\n\n";
    print "\n\n";
    exit(1);
}
print "\nNotice: \n";
print "\nThe ticket was moved to the queue 'Warehouse' and the state are changed to 'new'\n";

/**
 * TicketGet
 *
 * http://doc.otrs.com/doc/api/otrs/6.0/Perl/Kernel/GenericInterface/Operation/Ticket/TicketGet.pm.html
 */
$param = [
    'SessionID' => $SessionID,
];
$response = Unirest\Request::get($BaseURL."/Ticket/${TicketID}?Extended=1", $headers, $param);
if ($response->body && property_exists($response->body, 'Error')) {
        $ErrorCode = $response->body->Error->ErrorCode;
        $ErrorMessage = $response->body->Error->ErrorMessage;
        print "\n\n";
        print "ErrorCode $ErrorCode\n\n";
        print "ErrorMessage $ErrorMessage\n\n";
        print "\n\n";
        exit(1);
}
$TicketData = $response->body->Ticket[0];
print "\nTicket Details:\n";
foreach($TicketData as $key => $value) {
    if ($value) {
        print "$key: $value\n";
    }
}



/**
*
* SessionDestroy (Used to log out from Webservice account.)
*
*/
$param = [
'SessionID' => $SessionID,
];
$response = Unirest\Request::delete($BaseURL."/Session", $headers, $param);
if ($response->body && property_exists($response->body, 'Error')) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "\n\n";
    print "ErrorCode $ErrorCode\n\n";
    print "ErrorMessage $ErrorMessage\n\n";
    print "\n\n";
    exit(1);
}
print "\nNotice: \n";
print "\nSessionID $SessionID has been terminated.\n\n";

?>
