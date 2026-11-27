export type AiCommunicationType = 'announcement' | 'notification' | 'todo';

interface CommunicationDraft {
    title: string;
    category: string;
    target_grade_level_id: number | null;
    target_section_id: number | null;
}

export interface AnnouncementDraft extends CommunicationDraft {
    content: string;
    expires_at: string | null;
    is_pinned: boolean;
}

export interface TodoDraft extends CommunicationDraft {
    description: string;
    due_date: string | null;
    link_url: string | null;
}

export type AiCommunicationDraft = AnnouncementDraft | TodoDraft;
