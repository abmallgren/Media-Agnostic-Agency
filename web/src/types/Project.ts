export interface Project {
    id: number;
    name: string;
    description: string;
    involvementSought: string;
    isActive: boolean;
    ownerId: number;
    ownerName?: string;
    upvotesLast7Days: number;
    canEdit: boolean;
    canUpvote: boolean;
}