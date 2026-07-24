<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a guest tries to book using an email address that already belongs
 * to an existing account. Kept as its own type (rather than a bare RuntimeException)
 * so callers can tell "you typed someone else's email" apart from other booking
 * failures like "that slot just got taken" and react differently — see
 * BookAppointment::confirm(), which sends the user back to the contact step
 * (not the date/time step) and offers a login link when this specific exception
 * is caught.
 */
class AccountConflictException extends RuntimeException
{
}
