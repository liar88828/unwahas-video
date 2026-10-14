import React from 'react';
import { CctvVideoTile } from './cctv-video-tile';
import type { RoomUser } from '../types/webrtc';

export interface CctvVideoGridProps {
    localStream: MediaStream | null;
    remoteStreams: Map<string, MediaStream>;
    displayedPeers: RoomUser[];
    currentUserName: string;
    studentProgress?: Record<string, number>;
    totalQuestions?: number;
    onExpandStudent?: (student: { name: string; stream: MediaStream }) => void;
}

export function CctvVideoGrid({
    localStream,
    remoteStreams,
    displayedPeers,
    currentUserName,
    studentProgress = {},
    totalQuestions,
    onExpandStudent,
}: CctvVideoGridProps) {
    return (
        <div
            className={`grid gap-4 ${
                displayedPeers.length === 0
                    ? 'mx-auto max-w-xl grid-cols-1'
                    : displayedPeers.length === 1
                      ? 'mx-auto max-w-5xl grid-cols-1 md:grid-cols-2'
                      : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
            }`}
        >
            {/* Tile 00: Kamera Dosen Sendiri (Pengawas) */}
            <CctvVideoTile
                stream={localStream}
                studentName={`${currentUserName} (Anda)`}
                camIndex={0}
                isSelf={true}
                role="Dosen"
                onExpand={() =>
                    localStream &&
                    onExpandStudent?.({
                        name: `${currentUserName} (Dosen Pengawas)`,
                        stream: localStream,
                    })
                }
            />

            {/* Tiles 01..N: Kamera Mahasiswa Terhubung */}
            {displayedPeers.map((student, idx) => {
                const stream =
                    remoteStreams.get(student.id) ||
                    remoteStreams.get(String(student.id)) ||
                    null;
                const answeredCount =
                    studentProgress[student.id] ??
                    studentProgress[String(student.id)] ??
                    0;

                return (
                    <CctvVideoTile
                        key={student.id}
                        stream={stream}
                        studentName={student.name}
                        camIndex={idx + 1}
                        totalAnswered={answeredCount}
                        totalQuestions={totalQuestions}
                        role="Mahasiswa"
                        onExpand={() =>
                            stream &&
                            onExpandStudent?.({
                                name: student.name,
                                stream,
                            })
                        }
                    />
                );
            })}
        </div>
    );
}

export default CctvVideoGrid;
