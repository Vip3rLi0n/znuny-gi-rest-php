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

$TicketNumber = $response->body->TicketNumber;
$TicketID = $response->body->TicketID;
$ArticleID = $response->body->ArticleID;

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
if ( $response->body->Error ) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "\n\n";
    print "ErrorCode $ErrorCode\n\n";
    print "\n\n";
    print "ErrorMessage $ErrorMessage\n\n";
    print "\n\n";
    exit(1);
}

print "\nThe ticket was moved to the queue 'Warehouse' and the state changed to 'new'\n";

/**
 * TicketGet
 *
 * http://doc.otrs.com/doc/api/otrs/6.0/Perl/Kernel/GenericInterface/Operation/Ticket/TicketGet.pm.html
 */

/* NOTE: Fix this Later to Request ticket information */
$param = [
    'SessionID' => $SessionID,
];
$response = Unirest\Request::get($BaseURL."/Ticket/${TicketID}?Extended=1", $headers, $param);
if ( $response->body->Error ) {
        $ErrorCode = $response->body->Error->ErrorCode;
        $ErrorMessage = $response->body->Error->ErrorMessage;
        print "ErrorCode $ErrorCode\n\n";
        print "\n\n";
        print "ErrorMessage $ErrorMessage\n\n";
        print "\n\n";
        exit(1);
}
$ticketData = $response->body->Ticket[0];
print "\nThe ticket data:\n";
foreach($TicketData as $key => $value) {
    if ($value) {
        print "$key: $value\n";
    }
}

**
*
* SessionDestroy (Used to log out from Webservice account.)
*
*/
$param = [
'SessionID' => $SessionID,
];
$response = Unirest\Request::delete($BaseURL."/Session", $headers, $param);
if ( $response->body->Error ) {
    $ErrorCode = $response->body->Error->ErrorCode;
    $ErrorMessage = $response->body->Error->ErrorMessage;
    print "\n\n";
    print "ErrorCode $ErrorCode\n\n";
    print "ErrorMessage $ErrorMessage\n\n";
    print "\n\n";
    exit(1);
}
print "\nSessionID $SessionID has been terminated.\n\n";

?>
