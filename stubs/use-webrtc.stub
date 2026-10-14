import { useCallback, useEffect, useRef, useState } from 'react';
import { echo } from '../lib/echo';
import type {
    ConnectionStatus,
    PeerSignalMessage,
    RoomUser,
} from '../types/webrtc';

export type VideoQuality = '720p' | '480p' | '360p' | '240p';

export interface VideoQualityOption {
    label: string;
    width: number;
    height: number;
    frameRate: number;
    maxBitrate: number;
    description: string;
    tag: string;
}

export const VIDEO_QUALITY_CONFIG: Record<VideoQuality, VideoQualityOption> = {
    '720p': {
        label: 'HD 720p',
        width: 1280,
        height: 720,
        frameRate: 30,
        maxBitrate: 1500000,
        description: 'Kualitas Tinggi (~1.5 Mbps) - Standar',
        tag: 'HD',
    },
    '480p': {
        label: 'SD 480p',
        width: 854,
        height: 480,
        frameRate: 24,
        maxBitrate: 650000,
        description: 'Kualitas Sedang (~650 Kbps) - Hemat Bandwidth',
        tag: 'SD',
    },
    '360p': {
        label: 'Rendah 360p',
        width: 640,
        height: 360,
        frameRate: 20,
        maxBitrate: 350000,
        description: 'Sinyal Lemah (~350 Kbps) - Mengurangi Lag',
        tag: 'Low',
    },
    '240p': {
        label: 'Hemat Kuota 240p',
        width: 426,
        height: 240,
        frameRate: 15,
        maxBitrate: 150000,
        description: 'Sangat Rendah (~150 Kbps) - Sinyal Buruk / 3G',
        tag: 'Hemat',
    },
};

export const VIDEO_QUALITY_KEYS: readonly VideoQuality[] = [
    '720p',
    '480p',
    '360p',
    '240p',
] as const;

interface UseWebRTCOptions {
    roomSlug: string;
    currentUser: RoomUser;
    iceServers?: RTCIceServer[];
    enabled?: boolean;
    channelName: string;
    defaultResolution?: VideoQuality;
    initialAudioEnabled?: boolean;
    initialVideoEnabled?: boolean;
    signalEndpoint?: string;
}
const DEFAULT_ICE_SERVERS: RTCIceServer[] = [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
];

export function useWebRTC({
    roomSlug,
    currentUser,
    channelName,
    defaultResolution,
    initialAudioEnabled = true,
    initialVideoEnabled = true,
    iceServers = DEFAULT_ICE_SERVERS,
    enabled = true,
    signalEndpoint,
}: UseWebRTCOptions)
{
    const effectiveDefaultQuality = defaultResolution || '720p';

    const [localStream, setLocalStream] = useState<MediaStream | null>(null);
    const [ remoteStreams, setRemoteStreams ] = useState<Map<string, MediaStream>>( new Map() );
    const [peers, setPeers] = useState<RoomUser[]>([]);
    const [ isAudioMuted, setIsAudioMuted ] = useState(!initialAudioEnabled);
    const [ isVideoDisabled, setIsVideoDisabled ] = useState(!initialVideoEnabled);
    const [isScreenSharing, setIsScreenSharing] = useState(false);
    const [ activeScreenShareUserId, setActiveScreenShareUserId ] = useState<string | null>( null );
    const [ videoQuality, setVideoQuality ] = useState<VideoQuality>( effectiveDefaultQuality, );
    const [ connectionStatus, setConnectionStatus ] = useState<ConnectionStatus>( 'idle' );
    const [ errorMessage, setErrorMessage ] = useState<string | null>( null );

    const isAudioMutedRef = useRef(isAudioMuted);
    const isVideoDisabledRef = useRef(isVideoDisabled);

    useEffect(() => {
        isAudioMutedRef.current = isAudioMuted;
        isVideoDisabledRef.current = isVideoDisabled;
    });

    const peerConnections = useRef<Map<string, RTCPeerConnection>>( new Map(), );
    const peerStreams = useRef<Map<string, MediaStream>>(new Map());
    const iceCandidateQueue = useRef<Map<string, RTCIceCandidateInit[]>>( new Map() );
    const seenSignalIds = useRef<Set<string>>(new Set());
    const channelRef = useRef<any>(null);
    const [channel, setChannel] = useState<any>(null);
    const localStreamRef = useRef<MediaStream | null>(null);
    const localCameraStreamRef = useRef<MediaStream | null>(null);
    const screenStreamRef = useRef<MediaStream | null>(null);
    const videoQualityRef = useRef<VideoQuality>(effectiveDefaultQuality);

    // Standardize SDP string line breaks to strict CRLF (\r\n) per RFC 4566
    const sanitizeSdp = (sdpString?: string | null): string => {
        if (!sdpString) return '';
        const lines = sdpString
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n')
            .split('\n')
            .map((line) => line.trimEnd())
            .filter((line) => line.length > 0);
        return lines.join('\r\n') + '\r\n';
    };

    // Helper: Safely close and delete a peer connection
    const closePeerConnection = useCallback(
        (userId: string, targetPc?: RTCPeerConnection) => {
            const id = String(userId);
            const pc = peerConnections.current.get(id);

            // If a specific RTCPeerConnection was expected (e.g. from a timeout/handler),
            // ensure we don't close a newer reconnected RTCPeerConnection instance.
            if (targetPc && pc !== targetPc) {
                return;
            }

            if (pc) {
                pc.onicecandidate = null;
                pc.ontrack = null;
                pc.onconnectionstatechange = null;
                pc.close();
                peerConnections.current.delete(id);
            }
            peerStreams.current.delete(id);
            iceCandidateQueue.current.delete(id);
            setRemoteStreams((prev) => {
                const next = new Map(prev);
                next.delete(id);
                return next;
            });
        },
        [],
    );

    // Helper: Send signal with dual delivery (presence whisper + Reverb broadcast endpoint)
    const sendSignal = useCallback(
        async (targetId: string, payload: PeerSignalMessage) => {
            const signalPayload: PeerSignalMessage = {
                ...payload,
                id:
                    payload.id ||
                    `${currentUser.id}-${targetId}-${payload.type}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
            };

            if (signalPayload.id) {
                seenSignalIds.current.add(signalPayload.id);
            }

            const sdpSize = signalPayload.sdp?.sdp?.length ?? 0;
            console.log(
                `[WebRTC][${ channelName }] SEND ${ signalPayload.type } -> user ${ targetId } (sdp ${ sdpSize } bytes, channelReady=${ Boolean( channelRef.current ) })`,
            );

            // 1. Whisper directly on presence channel for zero-latency peer delivery
            if (channelRef.current) {
                try {
                    channelRef.current.whisper('webrtc-signal', signalPayload);
                } catch (err) {
                    console.warn('[WebRTC] whisper failed:', err);
                }
            }

            // 2. HTTP POST dispatch (/exams/signal or /rooms/signal) for server-backed Reverb delivery
            try {
                const csrfMeta = (
                    document.querySelector(
                        'meta[name="csrf-token"]',
                    ) as HTMLMetaElement
                )?.content;
                const xsrfCookie = document.cookie.match(
                    new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]+)'),
                )?.[2];
                const headers: Record<string, string> = {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                };
                if (csrfMeta) {
                    headers['X-CSRF-TOKEN'] = csrfMeta;
                } else if (xsrfCookie) {
                    headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie);
                }

                const endpoint =
                    signalEndpoint ??
                    (channelName.startsWith('exam-room.')
                        ? '/exams/signal'
                        : '/rooms/signal');

                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    body: JSON.stringify({
                        target_id: targetId,
                        signal: signalPayload,
                        room_slug: roomSlug,
                    }),
                });

                if (!response.ok) {
                    const body = await response.text().catch(() => '');
                    console.error(
                        `[WebRTC] HTTP ${endpoint} FAILED ${response.status}`,
                        body.slice(0, 500),
                    );
                }
            } catch (err) {
                console.warn('[WebRTC] Signaling broadcast warning:', err);
            }
        },
        [ currentUser.id, roomSlug, channelName ],
    );

    // Create RTCPeerConnection for a peer
    const createPeerConnection = useCallback(
        (targetUserId: string, stream: MediaStream) => {
            const id = targetUserId;
            const existingPc = peerConnections.current.get(id);
            if (existingPc && existingPc.signalingState !== 'closed') {
                return existingPc;
            }

            const pc = new RTCPeerConnection({
                iceServers,
                iceCandidatePoolSize: 10,
            });

            // Add local tracks to peer connection
            // CRITICAL: If screen sharing is active, send the live screen track instead of the camera track!
            const activeScreenTrack = screenStreamRef.current
                ?.getVideoTracks()
                .find((t) => t.readyState === 'live');
            const sourceStream =
                localCameraStreamRef.current ||
                localStreamRef.current ||
                stream;

            sourceStream.getTracks().forEach((track) => {
                if (track.kind === 'video' && activeScreenTrack) {
                    pc.addTrack(activeScreenTrack, sourceStream);
                } else {
                    pc.addTrack(track, sourceStream);
                }
            });

            // Listen for incoming remote tracks with fallback for mobile browsers
            pc.oniceconnectionstatechange = () => {
                console.log(
                    `[WebRTC] ICE state with peer ${targetUserId}: ${pc.iceConnectionState}`,
                );
            };

            pc.ontrack = (event) => {
                console.log(
                    `[WebRTC] Received remote track from peer ${targetUserId}:`,
                    event.track.kind,
                );
                let remoteStream = peerStreams.current.get(id);
                if (!remoteStream) {
                    remoteStream = event.streams?.[0]
                        ? event.streams[0]
                        : new MediaStream([event.track]);
                    peerStreams.current.set(id, remoteStream);
                } else {
                    if (
                        !remoteStream
                            .getTracks()
                            .some((t) => t.id === event.track.id)
                    ) {
                        remoteStream.addTrack(event.track);
                    }
                }

                // Wrap into a fresh MediaStream instance so React components detect track changes
                const streamClone = new MediaStream(remoteStream.getTracks());
                setRemoteStreams((prev) => {
                    const next = new Map(prev);
                    next.set(id, streamClone);
                    return next;
                });
            };

            // Transmit ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    sendSignal(targetUserId, {
                        senderId: currentUser.id,
                        targetId: targetUserId,
                        type: 'ice-candidate',
                        candidate: event.candidate.toJSON(),
                    } satisfies PeerSignalMessage);
                }
            };

            pc.onconnectionstatechange = () => {
                console.log(
                    `[WebRTC] Connection state with peer ${targetUserId}: ${pc.connectionState}`,
                );
                if (
                    pc.connectionState === 'connected' &&
                    !screenStreamRef.current
                ) {
                    const q = VIDEO_QUALITY_CONFIG[videoQualityRef.current];
                    const sender = pc
                        .getSenders()
                        .find((s) => s.track?.kind === 'video');
                    if (sender && q) {
                        try {
                            const p = sender.getParameters();
                            if (!p.encodings?.length) p.encodings = [{}];
                            p.encodings[0].maxBitrate = q.maxBitrate;
                            p.encodings[0].maxFramerate = q.frameRate;
                            sender.setParameters(p).catch(() => {});
                        } catch (_) {}
                    }
                }

                if (
                    pc.connectionState === 'failed' ||
                    pc.connectionState === 'closed'
                ) {
                    // if (peerConnections.current.get(id) === pc) {
                    //     closePeerConnection(targetUserId, pc);
                    // }
                    if ( peerConnections.current.get( id ) === pc )
                    {
                        const wasFailed = pc.connectionState === 'failed'; // simpan sebelum close
                        closePeerConnection( targetUserId, pc );

                        // Hanya initiator (ID lebih kecil) yang membuat panggilan baru
                        if ( wasFailed && currentUser.id < id && localStreamRef.current )
                        {
                            initiateCallRef.current( targetUserId, localStreamRef.current );
                        }
                    }
                } else if (pc.connectionState === 'disconnected') {
                    // if (currentUser.id < id) {
                    //     try {
                    //         pc.restartIce();
                    //     } catch (_) {}
                    // }
                    // Tolerate transient disconnects; close only if it does not recover and hasn't been replaced
                    setTimeout(() => {
                        if (
                            pc.connectionState === 'disconnected' &&
                            peerConnections.current.get(id) === pc
                        ) {
                            closePeerConnection(targetUserId, pc);
                        }
                    }, 5000);
                }
            };

            peerConnections.current.set(id, pc);
            return pc;
        },
        [closePeerConnection, currentUser.id, iceServers, sendSignal],
    );

    // Call an existing peer with an SDP offer
    const initiateCall = useCallback(
        async (targetUserId: string, stream: MediaStream) => {
            try {
                const pc = createPeerConnection(targetUserId, stream);
                const offer = await pc.createOffer();
                const sanitizedOfferSdp = sanitizeSdp(offer.sdp);
                const localOffer = new RTCSessionDescription({
                    type: 'offer',
                    sdp: sanitizedOfferSdp,
                });
                await pc.setLocalDescription(localOffer);

                const payload: PeerSignalMessage = {
                    senderId: currentUser.id,
                    targetId: targetUserId,
                    type: 'offer',
                    sdp: {
                        type: 'offer',
                        sdp: sanitizedOfferSdp,
                    },
                };

                await sendSignal(targetUserId, payload);

                // Watchdog: resend the same offer if no answer arrives (lost whisper / broadcast)
                let attempts = 0;
                const retry = () => {
                    attempts += 1;
                    if (
                        attempts > 3 ||
                        pc.signalingState !== 'have-local-offer' ||
                        !pc.localDescription
                    ) {
                        return;
                    }
                    console.warn(
                        `[WebRTC] No answer from peer ${targetUserId}, resending offer (#${attempts})`,
                    );
                    sendSignal(targetUserId, {
                        ...payload,
                        id: undefined,
                    }).finally(() => setTimeout(retry, 5000));
                };
                setTimeout(retry, 5000);
            } catch (err) {
                console.error(
                    `Failed to initiate call with peer ${targetUserId}:`,
                    err,
                );
            }
        },
        [createPeerConnection, currentUser.id, sendSignal],
    );

    // Handle incoming signals (offers, answers, ICE candidates)
    const handleSignalingMessage = useCallback(
        async (message: PeerSignalMessage) => {
            if (
                !message ||
                String(message.targetId) !== String(currentUser.id)
            ) {
                return;
            }

            // Deduplicate signal messages received via both whisper and HTTP broadcast
            if (message.id) {
                if (seenSignalIds.current.has(message.id)) {
                    return;
                }
                seenSignalIds.current.add(message.id);
                if (seenSignalIds.current.size > 1000) {
                    seenSignalIds.current.clear();
                }
            }

            console.log(
                `[WebRTC][${ channelName }] RECV ${ message.type } <- user ${ message.senderId }`,
            );
            const senderId = message.senderId;
            const stream = localStreamRef.current;
            if (!stream) return;

            let pc = peerConnections.current.get(senderId);

            try {
                if (message.type === 'offer' && message.sdp) {
                    if (!pc) {
                        pc = createPeerConnection(senderId, stream);
                    }

                    // Handle Glare / Collisions: if both peers made an offer, lower ID has priority
                    if (pc.signalingState !== 'stable') {
                        if (currentUser.id > senderId) {
                            // Yield to lower ID: rollback local offer
                            await pc.setLocalDescription({ type: 'rollback' });
                        } else {
                            // Lower ID maintains priority and ignores incoming collision offer
                            return;
                        }
                    }

                    const rawSdp =
                        typeof message.sdp === 'string'
                            ? message.sdp
                            : message.sdp.sdp;
                    const cleanOfferSdp = sanitizeSdp(rawSdp);
                    await pc.setRemoteDescription(
                        new RTCSessionDescription({
                            type: 'offer',
                            sdp: cleanOfferSdp,
                        }),
                    );

                    // Flush queued ICE candidates
                    const queued =
                        iceCandidateQueue.current.get(senderId) || [];
                    for (const cand of queued) {
                        await pc
                            .addIceCandidate(new RTCIceCandidate(cand))
                            .catch(() => {});
                    }
                    iceCandidateQueue.current.delete(senderId);

                    const answer = await pc.createAnswer();
                    const cleanAnswerSdp = sanitizeSdp(answer.sdp);
                    const localAnswer = new RTCSessionDescription({
                        type: 'answer',
                        sdp: cleanAnswerSdp,
                    });
                    await pc.setLocalDescription(localAnswer);

                    const payload: PeerSignalMessage = {
                        senderId: currentUser.id,
                        targetId: senderId,
                        type: 'answer',
                        sdp: {
                            type: 'answer',
                            sdp: cleanAnswerSdp,
                        },
                    };

                    await sendSignal(senderId, payload);
                } else if (message.type === 'answer' && message.sdp) {
                    if (!pc) return;
                    if (pc.signalingState !== 'have-local-offer') {
                        console.warn(
                            `[WebRTC] Ignored answer from peer ${senderId} because signalingState is '${pc.signalingState}'`,
                        );
                        return;
                    }

                    const rawSdp =
                        typeof message.sdp === 'string'
                            ? message.sdp
                            : message.sdp.sdp;
                    const cleanAnswerSdp = sanitizeSdp(rawSdp);
                    await pc.setRemoteDescription(
                        new RTCSessionDescription({
                            type: 'answer',
                            sdp: cleanAnswerSdp,
                        }),
                    );

                    // Flush queued ICE candidates
                    const queued =
                        iceCandidateQueue.current.get(senderId) || [];
                    for (const cand of queued) {
                        await pc
                            .addIceCandidate(new RTCIceCandidate(cand))
                            .catch(() => {});
                    }
                    iceCandidateQueue.current.delete(senderId);
                } else if (
                    message.type === 'ice-candidate' &&
                    message.candidate
                ) {
                    if (
                        pc &&
                        pc.remoteDescription &&
                        pc.remoteDescription.type
                    ) {
                        await pc
                            .addIceCandidate(
                                new RTCIceCandidate(message.candidate),
                            )
                            .catch(() => {});
                    } else {
                        const queue =
                            iceCandidateQueue.current.get(senderId) || [];
                        queue.push(message.candidate);
                        iceCandidateQueue.current.set(senderId, queue);
                    }
                }
            } catch (err) {
                console.error(
                    `Error processing signaling message from peer ${senderId}:`,
                    err,
                );
            }
        },
        [createPeerConnection, currentUser.id, sendSignal],
    );

    // Keep stable refs to callbacks to avoid re-running the heavy initialization effect
    const initiateCallRef = useRef(initiateCall);
    const handleSignalRef = useRef(handleSignalingMessage);
    const closePeerRef = useRef(closePeerConnection);
    useEffect(() => {
        initiateCallRef.current = initiateCall;
        handleSignalRef.current = handleSignalingMessage;
        closePeerRef.current = closePeerConnection;
    });

    // Initialize media and presence channel
    useEffect(() => {
        if (!enabled) {
            setConnectionStatus('idle');
            return;
        }

        let isMounted = true;

        async function initialize() {
            setConnectionStatus('requesting-permissions');
            setErrorMessage(null);

            try {
                if (
                    !navigator.mediaDevices ||
                    !navigator.mediaDevices.getUserMedia
                ) {
                    throw new Error(
                        'Browser hanya mengizinkan kamera pada Secure Context (HTTPS atau http://localhost). Jika membuka via IP (' +
                            window.location.hostname +
                            '), buka di Chrome flags: chrome://flags/#unsafely-treat-insecure-origin-as-secure dan tambahkan ' +
                            window.location.origin +
                            ' atau akses melalui http://localhost:8000.',
                    );
                }

                const activeQuality = defaultResolution || '720p';
                const currentQualityConfig = VIDEO_QUALITY_CONFIG[ activeQuality ] || VIDEO_QUALITY_CONFIG[ '720p' ];

                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: initialAudioEnabled
                        ? {
                              echoCancellation: true,
                              noiseSuppression: true,
                              autoGainControl: true,
                          }
                        : false,
                    video: {
                        width: { ideal: currentQualityConfig.width },
                        height: { ideal: currentQualityConfig.height },
                        frameRate: { max: currentQualityConfig.frameRate },
                    },
                });

                if (!isMounted) {
                    stream.getTracks().forEach((track) => track.stop());
                    return;
                }

                // Apply initial audio mute & video disabled preferences
                const audioTrack = stream.getAudioTracks()[0];
                if (audioTrack && isAudioMutedRef.current) {
                    audioTrack.enabled = false;
                }

                const videoTrack = stream.getVideoTracks()[0];
                if (videoTrack && isVideoDisabledRef.current) {
                    videoTrack.enabled = false;
                }

                localCameraStreamRef.current = stream;
                localStreamRef.current = stream;
                setLocalStream(stream);
                setConnectionStatus('connecting');

                const channel = echo.join( channelName );
                channelRef.current = channel;
                setChannel(channel);

                channel
                    .here((users: RoomUser[]) => {
                        console.log(
                            `[WebRTC][${ channelName }] HERE members:`,
                            users.map((u) => `${u.id}:${u.role ?? '?'}`),
                            `me=${currentUser.id}`,
                        );
                        if (!isMounted) return;
                        // Deduplicate peers by string ID and exclude current user
                        const uniqueMap = new Map<string, RoomUser>();
                        users.forEach((u) => {
                            if (u.id !== currentUser.id) {
                                uniqueMap.set(u.id, u);
                            }
                        });
                        const otherUsers = Array.from(uniqueMap.values());
                        setPeers(otherUsers);
                        setConnectionStatus('connected');

                        // Deterministic initiator: only peer with lower ID initiates call
                        otherUsers.forEach((user) => {
                            if (currentUser.id < user.id) {
                                initiateCallRef.current(user.id, stream);
                            }
                        });
                    })
                    .joining((user: RoomUser) => {
                        console.log(
                            `[WebRTC][${ channelName }] JOINING ${ user.id }:${ user.role ?? '?' }`,
                        );
                        if (!isMounted) return;
                        if (user.id === currentUser.id) return;
                        setPeers((prev) => {
                            if (prev.some((p) => p.id === user.id))
                                return prev;
                            return [...prev, user];
                        });

                        // Announce our active screen share status to newly joined/refreshed peer
                        if (
                            screenStreamRef.current
                                ?.getVideoTracks()
                                .some((t) => t.readyState === 'live')
                        ) {
                            channel.whisper('screen-share-status', {
                                userId: currentUser.id,
                                isSharing: true,
                            });
                        }

                        // Deterministic initiator: only peer with lower ID initiates call
                        if (currentUser.id < user.id) {
                            initiateCallRef.current(user.id, stream);
                        }
                    })
                    .leaving((user: RoomUser) => {
                        if (!isMounted) return;
                        setPeers((prev) =>
                            prev.filter((p) => p.id !== user.id),
                        );
                        closePeerRef.current(user.id);
                        setActiveScreenShareUserId((prev) =>
                            prev === user.id ? null : prev,
                        );
                    })
                    .error((err: any) => {
                        console.error(
                            'Presence channel authentication error:',
                            err,
                        );
                        setConnectionStatus('error');
                        setErrorMessage(
                            'Failed to connect to signaling server. Please check your network.',
                        );
                    });

                // Listen for screen share status changes across room members
                channel.listenForWhisper(
                    'screen-share-status',
                    (data: { userId: string; isSharing: boolean }) => {
                        if (!isMounted || !data?.userId) return;
                        if (data.isSharing) {
                            setActiveScreenShareUserId(data.userId);
                        } else {
                            setActiveScreenShareUserId((prev) =>
                                prev === data.userId ? null : prev,
                            );
                        }
                    },
                );

                // Listen for incoming WebRTC signals directly on the presence channel whisper
                channel.listenForWhisper(
                    'webrtc-signal',
                    (data: PeerSignalMessage) => {
                        if (!isMounted || !data) return;
                        console.log( "LOG:: data", data )
                        if (data.targetId === currentUser.id) {
                            handleSignalRef.current(data);
                        }
                    },
                );

                // Listen for incoming WebRTC signals broadcasted from server VideoSignalReceived
                const onServerSignal = (data: {
                    signal?: PeerSignalMessage;
                    userId?: string;
                }) => {
                    if (!isMounted || !data?.signal) return;
                    if (data.userId === currentUser.id) {
                        handleSignalRef.current(data.signal);
                    }
                };
                channel.listen('VideoSignalReceived', onServerSignal);
                channel.listen('.VideoSignalReceived', onServerSignal);
            } catch (err: any) {
                if (!isMounted) return;
                console.error('WebRTC initialization failed:', err);
                setConnectionStatus('error');
                if (
                    err.name === 'NotAllowedError' ||
                    err.name === 'PermissionDeniedError'
                ) {
                    setErrorMessage(
                        'Camera or microphone permission was denied. Please allow access.',
                    );
                } else if (
                    err.name === 'NotFoundError' ||
                    err.name === 'DevicesNotFoundError'
                ) {
                    setErrorMessage(
                        'No camera or microphone found on this device.',
                    );
                } else {
                    setErrorMessage(
                        err.message || 'Unable to access media devices.',
                    );
                }
            }
        }

        initialize();

        return () => {
            isMounted = false;

            // Close all peer connections
            peerConnections.current.forEach((pc) => {
                pc.onicecandidate = null;
                pc.ontrack = null;
                pc.onconnectionstatechange = null;
                pc.close();
            });
            peerConnections.current.clear();
            peerStreams.current.clear();
            iceCandidateQueue.current.clear();
            seenSignalIds.current.clear();
            setLocalStream(null);
            setPeers([]);
            setRemoteStreams(new Map());

            // Stop local stream tracks (including camera when screen share is active)
            [localStreamRef.current, localCameraStreamRef.current].forEach(
                (s) => s?.getTracks().forEach((t) => t.stop()),
            );
            localStreamRef.current = null;
            localCameraStreamRef.current = null;
            setIsScreenSharing(false);
            setActiveScreenShareUserId(null);

            // Stop screen stream tracks if any
            if (screenStreamRef.current) {
                screenStreamRef.current
                    .getTracks()
                    .forEach((track) => track.stop());
                screenStreamRef.current = null;
            }

            // Leave presence channel
            if (channelRef.current) {
                echo.leave( channelName );
                channelRef.current = null;
                setChannel(null);
            }
        };
    }, [ enabled, channelName, currentUser.id ] );

    // Media Controls
    const toggleAudio = useCallback(async () => {
        const cam = localCameraStreamRef.current || localStreamRef.current;
        if (!cam) return;
        let audioTrack = cam.getAudioTracks()[0];

        if (!audioTrack || audioTrack.readyState === 'ended') {
            try {
                const media = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    },
                });
                const newTrack = media.getAudioTracks()[0];
                if (audioTrack) cam.removeTrack(audioTrack);
                cam.addTrack(newTrack);
                audioTrack = newTrack;

                peerConnections.current.forEach((pc) => {
                    const sender = pc
                        .getSenders()
                        .find((s) => s.track?.kind === 'audio');
                    if (sender) {
                        sender.replaceTrack(newTrack);
                    } else {
                        pc.addTrack(newTrack, cam);
                    }
                });

                setIsAudioMuted(false);
                if (localStreamRef.current) {
                    setLocalStream(new MediaStream(cam.getTracks()));
                }
                return;
            } catch (err) {
                console.error('[WebRTC] Failed to acquire audio track:', err);
                return;
            }
        }

        audioTrack.enabled = !audioTrack.enabled;
        const nextMuted = !audioTrack.enabled;
        setIsAudioMuted(nextMuted);

        // Clone only for React state so refs stay stable
        if (localStreamRef.current) {
            setLocalStream(
                new MediaStream(localStreamRef.current.getTracks()),
            );
        }
    }, []);

    const toggleVideo = useCallback(async () => {
        const cam = localCameraStreamRef.current;
        if (!cam) return;
        let videoTrack = cam.getVideoTracks()[0];

        if (!videoTrack || videoTrack.readyState === 'ended') {
            try {
                const q =
                    VIDEO_QUALITY_CONFIG[videoQualityRef.current] ||
                    VIDEO_QUALITY_CONFIG['720p'];
                const media = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: q.width },
                        height: { ideal: q.height },
                        frameRate: { max: q.frameRate },
                    },
                });
                const newTrack = media.getVideoTracks()[0];
                if (videoTrack) cam.removeTrack(videoTrack);
                cam.addTrack(newTrack);
                videoTrack = newTrack;

                if (!screenStreamRef.current) {
                    peerConnections.current.forEach((pc) => {
                        pc.getSenders()
                            .find((s) => s.track?.kind === 'video')
                            ?.replaceTrack(newTrack);
                    });
                }
                // track baru sudah enabled; jangan di-toggle lagi
                setIsVideoDisabled(false);
                if (!screenStreamRef.current)
                    setLocalStream(new MediaStream(cam.getTracks()));
                return;
            } catch (err) {
                console.error(
                    '[WebRTC] Failed to re-acquire video track:',
                    err,
                );
                return;
            }
        }

        videoTrack.enabled = !videoTrack.enabled;
        const nextDisabled = !videoTrack.enabled;
        setIsVideoDisabled(nextDisabled);
        if (!screenStreamRef.current)
            setLocalStream(new MediaStream(cam.getTracks()));
    }, []);

    const toggleScreenShare = useCallback(async () => {
        if (!isScreenSharing) {
            try {
                const screenStream =
                    await navigator.mediaDevices.getDisplayMedia({
                        video: true,
                        audio: false,
                    });
                screenStreamRef.current = screenStream;
                const screenTrack = screenStream.getVideoTracks()[0];

                // Replace local video tracks in existing peer connections with screen track
                peerConnections.current.forEach((pc) => {
                    const sender = pc
                        .getSenders()
                        .find((s) => s.track?.kind === 'video');
                    if (sender) {
                        sender.replaceTrack(screenTrack);
                        // Unthrottle bitrate on screen share track so text/content remains crisp
                        try {
                            const params = sender.getParameters();
                            if (params.encodings?.[0]) {
                                delete params.encodings[0].maxBitrate;
                                delete params.encodings[0].maxFramerate;
                                sender.setParameters(params).catch(() => {});
                            }
                        } catch (_) {}
                    }
                });

                // Update localStream so local user sees their shared screen in their tile
                const audioTrack =
                    localCameraStreamRef.current?.getAudioTracks()[0] ||
                    localStreamRef.current?.getAudioTracks()[0];
                const displayStream = new MediaStream(
                    audioTrack ? [screenTrack, audioTrack] : [screenTrack],
                );
                localStreamRef.current = displayStream;
                setLocalStream(displayStream);
                setIsScreenSharing(true);
                setActiveScreenShareUserId(currentUser.id);

                // Notify all peers in room of our screen share
                channelRef.current?.whisper('screen-share-status', {
                    userId: currentUser.id,
                    isSharing: true,
                });

                screenTrack.onended = () => {
                    // Revert back to local camera track
                    const cameraTrack =
                        localCameraStreamRef.current?.getVideoTracks()[0] ??
                        null;
                    const q = VIDEO_QUALITY_CONFIG[videoQualityRef.current];
                    peerConnections.current.forEach((pc) => {
                        const sender = pc
                            .getSenders()
                            .find((s) => s.track?.kind === 'video');
                        if (sender && cameraTrack) {
                            sender.replaceTrack(cameraTrack);
                            if (q) {
                                try {
                                    const params = sender.getParameters();
                                    if (!params.encodings?.length)
                                        params.encodings = [{}];
                                    params.encodings[0].maxBitrate =
                                        q.maxBitrate;
                                    params.encodings[0].maxFramerate =
                                        q.frameRate;
                                    sender
                                        .setParameters(params)
                                        .catch(() => {});
                                } catch (_) {}
                            }
                        }
                    });
                    screenStreamRef.current = null;
                    if (localCameraStreamRef.current) {
                        localStreamRef.current = localCameraStreamRef.current;
                        setLocalStream(
                            new MediaStream(
                                localCameraStreamRef.current.getTracks(),
                            ),
                        );
                    }
                    setIsScreenSharing(false);
                    setActiveScreenShareUserId((prev) =>
                        String(prev) === String(currentUser.id) ? null : prev,
                    );

                    channelRef.current?.whisper('screen-share-status', {
                        userId: currentUser.id,
                        isSharing: false,
                    });
                };
            } catch (err) {
                console.warn('Screen share canceled or denied:', err);
            }
        } else {
            // Stop screen share and revert back to camera
            if (screenStreamRef.current) {
                screenStreamRef.current
                    .getTracks()
                    .forEach((track) => track.stop());
                screenStreamRef.current = null;
            }
            const cameraTrack =
                localCameraStreamRef.current?.getVideoTracks()[0] ?? null;
            const q = VIDEO_QUALITY_CONFIG[videoQualityRef.current];
            peerConnections.current.forEach((pc) => {
                const sender = pc
                    .getSenders()
                    .find((s) => s.track?.kind === 'video');
                if (sender && cameraTrack) {
                    sender.replaceTrack(cameraTrack);
                    if (q) {
                        try {
                            const params = sender.getParameters();
                            if (!params.encodings?.length)
                                params.encodings = [{}];
                            params.encodings[0].maxBitrate = q.maxBitrate;
                            params.encodings[0].maxFramerate = q.frameRate;
                            sender.setParameters(params).catch(() => {});
                        } catch (_) {}
                    }
                }
            });
            if (localCameraStreamRef.current) {
                localStreamRef.current = localCameraStreamRef.current;
                setLocalStream(
                    new MediaStream(localCameraStreamRef.current.getTracks()),
                );
            }
            setIsScreenSharing(false);
            setActiveScreenShareUserId((prev) =>
                String(prev) === String(currentUser.id) ? null : prev,
            );

            channelRef.current?.whisper('screen-share-status', {
                userId: currentUser.id,
                isSharing: false,
            });
        }
    }, [currentUser.id, isScreenSharing]);

    // Video Resolution & Bandwidth Adjustment
    const changeVideoQuality = useCallback(async (quality: VideoQuality) => {
        setVideoQuality(quality);
        videoQualityRef.current = quality;
        const config = VIDEO_QUALITY_CONFIG[quality];

        // 1. Apply constraints to camera hardware track
        const videoTrack = localCameraStreamRef.current?.getVideoTracks()[0];

        if (videoTrack && videoTrack.readyState === 'live') {
            try {
                await videoTrack.applyConstraints({
                    width: { ideal: config.width },
                    height: { ideal: config.height },
                    frameRate: { max: config.frameRate },
                });
            } catch (err) {
                console.warn(
                    '[WebRTC] applyConstraints error on videoTrack:',
                    err,
                );
            }
        }

        // 2. Set maxBitrate and maxFramerate on all active peer senders (skip when screen sharing)
        if (!screenStreamRef.current) {
            peerConnections.current.forEach((pc) => {
                const sender = pc
                    .getSenders()
                    .find((s) => s.track?.kind === 'video');
                if (sender) {
                    try {
                        const params = sender.getParameters();
                        if (
                            !params.encodings ||
                            params.encodings.length === 0
                        ) {
                            params.encodings = [{}];
                        }
                        params.encodings[0].maxBitrate = config.maxBitrate;
                        params.encodings[0].maxFramerate = config.frameRate;
                        sender.setParameters(params).catch((e) => {
                            console.warn(
                                '[WebRTC] setParameters maxBitrate error:',
                                e,
                            );
                        });
                    } catch (e) {
                        console.warn('[WebRTC] getParameters error:', e);
                    }
                }
            });
        }
    }, []);

    return {
        channel,
        localStream,
        remoteStreams,
        peers,
        isAudioMuted,
        isVideoDisabled,
        isScreenSharing,
        activeScreenShareUserId,
        videoQuality,
        changeVideoQuality,
        connectionStatus,
        errorMessage,
        toggleAudio,
        toggleVideo,
        toggleScreenShare,
    };
}
