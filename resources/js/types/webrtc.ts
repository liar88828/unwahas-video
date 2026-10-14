export interface RoomUser {
    id: string;
    name: string;
    email?: string;
    role?: string;
    avatar?: string;
}

export type ConnectionStatus =
    | 'idle'
    | 'requesting-permissions'
    | 'connecting'
    | 'connected'
    | 'error';

export type SignalType = 'offer' | 'answer' | 'ice-candidate';

export interface PeerSignalMessage {
    id?: string;
    senderId: string;
    targetId: string;
    type: SignalType;
    sdp?: RTCSessionDescriptionInit;
    candidate?: RTCIceCandidateInit;
}

export interface VideoRoomItem {
    id: string;
    name: string;
    slug: string;
    user_id: string | null;
    is_started: boolean;
    started_at?: string | null;
    created_at: string;
    user?: {
        id: string;
        name: string;
        role?: string;
    } | null;
}
