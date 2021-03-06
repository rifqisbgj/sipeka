<?php

namespace App\Http\Controllers;

use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class CalendarController extends Controller
{
    private $cilent;

    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setApplicationName(env('APP_TITLE'));
        $this->client->setDeveloperKey(env('GOOGLE_SERVER_KEY'));
    }

    public function getEvents()
    {
        $this->client->setAccessToken(Auth::user()->token);

        $service = new \Google_Service_Calendar($this->client);

        if (Auth::user()->calendar_id == '') {
            $data = User::find(Auth::id());
            $data->calendar_id = $this->createCalendar();
            $data->save();
        }
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents(Auth::user()->calendar_id, $optParams);
        $events = $results->getItems();
        
        return view('events', compact('events'));
    }


    public function createCalendar()
    {
        $this->client->setAccessToken(Auth::user()->token);
        $service = new \Google_Service_Calendar($this->client);

        $meet = new \Google_Service_Calendar_ConferenceProperties;
        $meet->setAllowedConferenceSolutionTypes(['hangoutsMeet']);

        $calendar = new \Google_Service_Calendar_Calendar();
        $calendar->setSummary('[SiPeka]');
        $calendar->setTimeZone('Asia/Jakarta');
        $calendar->setConferenceProperties($meet);
        $calendar->setDescription("Kalender Otomatis Yang Dibuat Oleh SiPeka");

        $createdCalendar = $service->calendars->insert($calendar);

        return $createdCalendar->getId();

    }
    
    public function createEvent()
    {

    }

    public function createEventConference()
    {
        $this->client->setAccessToken(Auth::user()->token);
        $service = new \Google_Service_Calendar($this->client);

        $event = new \Google_Service_Calendar_Event(array(
            'summary' => 'Appointment',
            'location' => 'Indonesia',
            'description' => 'Hello world',
            'start' => array(
                'dateTime' => \Carbon\Carbon::now()->format('c'),
                'timeZone' => 'Asia/Jakarta',
            ),
            'end' => array(
                'dateTime' => \Carbon\Carbon::now()->addMinutes(90)->format('c'),
                'timeZone' => 'Asia/Jakarta',
            )
        )); 
        $event->setAttendees([
            ['email' => 'priyayidimas@gmail.com'],
            ['email' => 'mendozadante05@gmail.com']
        ]);
        $calendarId = Auth::user()->calendar_id;
        $event = $service->events->insert($calendarId, $event);
        
        printf('Event created: %s', $event->htmlLink);
        
        $conference = new \Google_Service_Calendar_ConferenceData();
        $conferenceRequest = new \Google_Service_Calendar_CreateConferenceRequest();
        $conferenceRequest->setRequestId('IniMeeting');
        $conference->setCreateRequest($conferenceRequest);
        $event->setConferenceData($conference);
        
        $event = $service->events->patch($calendarId, $event->id, $event, ['conferenceDataVersion' => 1]);
        
        printf('<br>Conference created: %s', $event->hangoutLink);
    }

    // DEBUG
    public function getPeople()
    {
        $this->client->setAccessToken(Auth::user()->token);

        $service = new \Google_Service_PeopleService($this->client);

        $optParams = array('requestMask.includeField' => 'person.phone_numbers,person.names,person.email_addresses');
        $results = $service->people_connections->listPeopleConnections('people/me',$optParams);

        return view('contacts', compact('results'));
    }
}
