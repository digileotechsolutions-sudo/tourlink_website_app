<?php

namespace App;

enum OperatorVerificationDocument: string
{
    case RegistrationCertificate = 'registration_certificate';
    case KraPin = 'kra_pin';
    case TourOperatorLicense = 'tour_operator_license';
    case BusinessPermit = 'business_permit';
    case RepresentativeId = 'representative_id';
    case BusinessAddressProof = 'business_address_proof';
    case BusinessBankDetails = 'business_bank_details';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationCertificate => 'Business registration or certificate of incorporation',
            self::KraPin => 'Business KRA PIN certificate',
            self::TourOperatorLicense => 'Tour operator license or authorization',
            self::BusinessPermit => 'Current business permit',
            self::RepresentativeId => 'Owner, director, or authorized representative ID/passport',
            self::BusinessAddressProof => 'Business address proof',
            self::BusinessBankDetails => 'Business bank confirmation',
        };
    }

    public function guidance(): string
    {
        return match ($this) {
            self::RegistrationCertificate => 'Upload the business registration certificate or, for a company, the certificate of incorporation.',
            self::KraPin => 'Use a valid KRA PIN certificate registered to the business.',
            self::TourOperatorLicense => 'Upload a valid tourism or tour-operator license, or other relevant authorization.',
            self::BusinessPermit => 'Upload the current county or business operating permit, where applicable.',
            self::RepresentativeId => 'Provide the ID or passport of the business owner, director, or authorized representative.',
            self::BusinessAddressProof => 'A lease agreement, utility bill, or other accepted proof of the business address.',
            self::BusinessBankDetails => 'Optional unless required for payments. Use a bank letter or statement showing the business name.',
        };
    }

    public function required(): bool
    {
        return $this !== self::BusinessBankDetails;
    }

    /** @return list<string> */
    public static function requiredKeys(): array
    {
        return array_values(array_map(
            fn (self $document): string => $document->value,
            array_filter(self::cases(), fn (self $document): bool => $document->required()),
        ));
    }
}
