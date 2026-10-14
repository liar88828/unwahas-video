import { useEffect, useRef, useState } from 'react';
import { Maximize2, ShieldCheck, VideoOff, VolumeX } from 'lucide-react';
import { formatDate } from '../lib/format-date';

export interface CctvVideoTileProps {
    stream: MediaStream | null;
    studentName: string;
    camIndex: number;
    totalAnswered?: number;
    totalQuestions?: number;
    isSelf?: boolean;
    role?: string;
    onExpand?: () => void;
}

// CCTV Camera Tile for Host / Dosen Surveillance Matrix
export function CctvVideoTile({
    stream,
    studentName,
    camIndex,
    totalAnswered,
    totalQuestions,
    isSelf = false,
    role = 'Mahasiswa',
    onExpand,
}: CctvVideoTileProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [currentTime, setCurrentTime] = useState('');

    useEffect(() => {
        const videoEl = videoRef.current;
        if (videoEl && stream) {
            if (videoEl.srcObject !== stream) {
                videoEl.srcObject = stream;
            }
            videoEl.play().catch(() => {
                videoEl.muted = true;
                videoEl.play().catch(() => {});
            });
        }
    }, [stream]);

    useEffect(() => {
        const updateClock = () => {
            setCurrentTime(formatDate());
        };
        updateClock();
        const interval = setInterval(updateClock, 1000);
        return () => clearInterval(interval);
    }, []);

    const isDosenTile = isSelf || role.toLowerCase() === 'dosen';

    return (
        <div
            className={`group relative flex aspect-video w-full flex-col overflow-hidden rounded-xl border-2 bg-black shadow-2xl transition-all ${
                isDosenTile
                    ? 'border-indigo-500/60 hover:border-indigo-400'
                    : 'border-neutral-800 hover:border-emerald-500/50'
            }`}
        >
            {/* Surveillance OSD Header Overlay */}
            <div className="absolute inset-x-0 top-0 z-20 flex items-center justify-between bg-gradient-to-b from-black/80 via-black/40 to-transparent p-2.5 font-mono text-[11px] text-emerald-400">
                <div className="flex items-center gap-2">
                    <span className="flex items-center gap-1 font-bold tracking-wider">
                        <span
                            className={`h-2 w-2 rounded-full ${
                                isDosenTile ? 'bg-indigo-400' : 'bg-red-500'
                            } animate-ping`}
                        />
                        {isDosenTile ? 'HOST' : 'REC'}
                    </span>
                    <span className="text-neutral-400">|</span>
                    <span className="font-semibold text-white">
                        {isDosenTile
                            ? 'CAM 00 (DOSEN)'
                            : `CAM ${String(camIndex).padStart(2, '0')}`}
                    </span>
                    <span className="text-neutral-400">•</span>
                    <span className="max-w-[140px] truncate text-neutral-200">
                        {studentName}
                    </span>
                </div>

                <div className="flex items-center gap-2 font-mono">
                    <span className="hidden text-neutral-400 sm:inline">
                        {currentTime} WIB
                    </span>
                    {onExpand && (
                        <button
                            type="button"
                            onClick={onExpand}
                            className="rounded p-1 text-neutral-400 transition-colors hover:bg-neutral-800/80 hover:text-white cursor-pointer"
                            title="Perbesar Layar CCTV"
                        >
                            <Maximize2 className="h-3.5 w-3.5" />
                        </button>
                    )}
                </div>
            </div>

            {/* Video Viewport */}
            <div className="relative flex h-full w-full flex-1 items-center justify-center bg-neutral-950">
                <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className={`h-full w-full object-cover transition-opacity duration-300 ${
                        isSelf ? 'scale-x-[-1]' : ''
                    } ${!stream ? 'pointer-events-none absolute inset-0 opacity-0' : 'opacity-100'}`}
                />

                {!stream && (
                    <div className="flex flex-col items-center justify-center gap-2 font-mono text-xs text-neutral-500">
                        <VideoOff className="h-8 w-8 animate-pulse text-neutral-600" />
                        <span>
                            {isDosenTile
                                ? 'KAMERA DOSEN OFF'
                                : 'MENUNGGU SINYAL VIDEO...'}
                        </span>
                    </div>
                )}
            </div>

            {/* Bottom Status Overlay */}
            <div className="absolute inset-x-0 bottom-0 z-20 flex items-center justify-between bg-gradient-to-t from-black/90 via-black/50 to-transparent px-3 py-2 font-mono text-[11px]">
                <div className="flex items-center gap-2">
                    <span
                        className={`inline-flex items-center gap-1 ${
                            isDosenTile ? 'text-indigo-400' : 'text-emerald-400'
                        }`}
                    >
                        <ShieldCheck className="h-3 w-3" />
                        {isDosenTile ? 'PENGAWAS AKTIF' : 'PROCTORING AKTIF'}
                    </span>
                    <span className="text-neutral-500">•</span>
                    <span className="flex items-center gap-1 text-neutral-400">
                        <VolumeX className="h-3 w-3 text-amber-400" />
                        Audio Muted
                    </span>
                </div>

                {!isDosenTile &&
                    typeof totalAnswered === 'number' &&
                    typeof totalQuestions === 'number' && (
                        <span className="rounded border border-neutral-700 bg-neutral-800/90 px-2 py-0.5 text-neutral-200">
                            Soal: {totalAnswered}/{totalQuestions}
                        </span>
                    )}
            </div>
        </div>
    );
}

export interface ProctorVideoTileProps {
    stream: MediaStream | null;
    dosenName: string;
    isConnected: boolean;
}

export function ProctorVideoTile({
    stream,
    dosenName,
    isConnected,
}: ProctorVideoTileProps) {
    const videoRef = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        const videoEl = videoRef.current;
        if (videoEl && stream) {
            if (videoEl.srcObject !== stream) {
                videoEl.srcObject = stream;
            }
            videoEl.play().catch(() => {
                videoEl.muted = true;
                videoEl.play().catch(() => {});
            });
        }
    }, [stream]);

    return (
        <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span
                        className={`h-2.5 w-2.5 rounded-full ${
                            isConnected
                                ? 'animate-pulse bg-emerald-500'
                                : 'bg-amber-500'
                        }`}
                    />
                    <h3 className="font-mono text-xs font-bold tracking-wider text-white uppercase">
                        Kamera Dosen Pengawas
                    </h3>
                </div>
                <span
                    className={`rounded px-2 py-0.5 font-mono text-[10px] ${
                        isConnected
                            ? 'border border-indigo-500/30 bg-indigo-500/20 text-indigo-300'
                            : 'border border-neutral-700 bg-neutral-800 text-neutral-400'
                    }`}
                >
                    {isConnected ? 'LIVE' : 'MENUNGGU'}
                </span>
            </div>

            <div className="relative flex aspect-video w-full items-center justify-center overflow-hidden rounded-xl border-2 border-indigo-500/50 bg-black shadow-xl">
                <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className={`h-full w-full object-cover transition-opacity duration-300 ${
                        !stream
                            ? 'pointer-events-none absolute inset-0 opacity-0'
                            : 'opacity-100'
                    }`}
                />

                {!stream && (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 p-4 text-center font-mono text-xs text-neutral-500">
                        <VideoOff className="h-8 w-8 animate-pulse text-neutral-600" />
                        <span>Menunggu Kamera Dosen...</span>
                        <span className="font-sans text-[10px] text-neutral-400">
                            {dosenName}
                        </span>
                    </div>
                )}

                <div className="absolute top-2 left-2 flex items-center gap-1.5 rounded border border-indigo-500/30 bg-black/80 px-2 py-0.5 font-mono text-[10px] text-indigo-300">
                    <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-400" />
                    DOSEN PENGAWAS
                </div>

                <div className="absolute bottom-2 left-2 rounded bg-black/80 px-2 py-0.5 font-mono text-[10px] text-white">
                    {dosenName}
                </div>
            </div>
        </div>
    );
}

export interface StudentSelfCameraTileProps {
    stream: MediaStream | null;
    studentName: string;
}

export function StudentSelfCameraTile({
    stream,
    studentName,
}: StudentSelfCameraTileProps) {
    const videoRef = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        const videoEl = videoRef.current;
        if (videoEl && stream) {
            if (videoEl.srcObject !== stream) {
                videoEl.srcObject = stream;
            }
            videoEl.play().catch(() => {
                videoEl.muted = true;
                videoEl.play().catch(() => {});
            });
        }
    }, [stream]);

    return (
        <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="h-2.5 w-2.5 animate-ping rounded-full bg-red-500" />
                    <h3 className="font-mono text-xs font-bold tracking-wider text-white uppercase">
                        Kamera Anda (Mahasiswa)
                    </h3>
                </div>
                <span className="rounded border border-emerald-500/30 bg-emerald-500/20 px-2 py-0.5 font-mono text-[10px] text-emerald-300">
                    REC ACTIVE
                </span>
            </div>

            <div className="relative flex aspect-video w-full items-center justify-center overflow-hidden rounded-xl border-2 border-emerald-500/50 bg-black shadow-xl">
                <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className={`h-full w-full -scale-x-100 object-cover transition-opacity duration-300 ${
                        !stream
                            ? 'pointer-events-none absolute inset-0 opacity-0'
                            : 'opacity-100'
                    }`}
                />

                {!stream && (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 p-4 text-center font-mono text-xs text-neutral-500">
                        <VideoOff className="h-8 w-8 animate-pulse text-neutral-600" />
                        <span>Kamera Anda Mati</span>
                    </div>
                )}

                <div className="absolute top-2 left-2 flex items-center gap-1.5 rounded border border-emerald-500/30 bg-black/80 px-2 py-0.5 font-mono text-[10px] text-emerald-400">
                    <span className="h-1.5 w-1.5 animate-ping rounded-full bg-red-500" />
                    LIVE REC
                </div>

                <div className="absolute bottom-2 left-2 rounded bg-black/80 px-2 py-0.5 font-mono text-[10px] text-white">
                    {studentName} (Anda)
                </div>
            </div>
        </div>
    );
}

export default CctvVideoTile;
