<?php

namespace RavenDB\Auth;

class PfxCertificate extends Certificate
{
    public function __construct(string $certificate, string $passphrase, string $ca)
    {
        parent::__construct($certificate, $passphrase, $ca);
    }

//    public toAgentOptions(): AgentOptions {
//        return Object.assign(super.toAgentOptions(), {
//            pfx: this._certificate as Buffer,
//            ca: this._ca
//        });
//    }
//
//    public toWebSocketOptions(): WebSocket.ClientOptions {
//        const result = super.toWebSocketOptions();
//        return Object.assign(result, {
//            pfx: this._certificate as Buffer,
//            ca: this._ca
//        });
//    }
}
