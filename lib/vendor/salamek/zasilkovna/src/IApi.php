<?php
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 7.9.17
 * Time: 6:45
 */

namespace Salamek\Zasilkovna;


use Salamek\Zasilkovna\Model\ClaimAttributes;
use Salamek\Zasilkovna\Model\PacketAttributes;

interface IApi
{
    public function packetAttributesValid(PacketAttributes $attributes);

    public function packetClaimAttributesValid(ClaimAttributes $attributes);

    public function createPacket(PacketAttributes $attributes);

    public function createPacketClaim(ClaimAttributes $attributes);

    public function createShipment(/*int*/ $packetId, /*string*/ $customBarcode);

    public function packetStatus(/*int*/ $packetId);

    public function packetTracking(/*int*/ $packetId);

    public function packetGetStoredUntil(/*int*/ $packetId);

    public function packetSetStoredUntil(/*int*/ $packetId, \DateTimeInterface $date);

    public function barcodePng(/*string*/ $barcode);

    public function packetLabelPdf(/*int*/ $packetId, /*string*/ $format, /*int*/ $offset);

    public function packetsLabelsPdf(array/*PacketIds*/ $packetIds, /*string*/ $format, /*int*/ $offset);

    public function packetCourierNumber(/*int*/ $packetId);

    public function senderGetReturnRouting(/*string*/ $senderLabel);
}