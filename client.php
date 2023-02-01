#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';
use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Message\MessageFactory\GuzzleMessageFactory;

$httpClient = new HttpMethodsClient(
    HttpClientDiscovery::find(),
    new GuzzleMessageFactory()
);

$FQDN = 'FIXME';
$WebServiceName = 'GenericTicketConnectorREST';
$BaseURL = "https://$FQDN/otrs/nph-genericinterface.pl/Webservice/$WebServiceName";
$headers = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json'
];
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
$client = new \GuzzleHttp\Client();
$response = $client->request('POST', $BaseURL."/Session", [
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    'verify' => true,
    'body' => $body,
]);

$responseData = json_decode($response->getBody());

if (!$responseData->SessionID) {
    print "No SessionID returned \n";
    exit(1);
}

$SessionID = $responseData->SessionID;
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

$response = $client->request('POST', $BaseURL."/Ticket", [
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    'verify' => true,
    'body' => $body,
]);

$responseData = json_decode($response->getBody(), true);

if (array_key_exists('Error', $responseData)) {
    $ErrorCode = $responseData['Error']['ErrorCode'];
    $ErrorMessage = $responseData['Error']['ErrorMessage'];
    print "ErrorCode $ErrorCode\n";
    print "ErrorMessage $ErrorMessage\n";
    exit(1);
}

$TicketNumber = $responseData['TicketNumber'];
$TicketID = $responseData['TicketID'];

print "https://$FQDN/otrs/index.pl?Action=AgentTicketZoom;TicketID=$TicketID \n";
