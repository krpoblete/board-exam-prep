export type Difficulty = 'easy' | 'medium' | 'hard';
export type QuestionStatus = 'draft' | 'published';

export interface TopicOption {
    id: number;
    subject_id: number;
    name: string;
}
export interface SubjectOption {
    id: number;
    name: string;
    topics: TopicOption[];
}
export interface TagOption {
    id: number;
    name: string;
    slug: string;
}

export interface QuestionRow {
    id: number;
    body: string;
    difficulty: Difficulty;
    status: QuestionStatus;
    choices_count: number;
    topic: {
        id: number;
        name: string;
        subject: {
            id: number;
            name: string;
        };
    };
    tags: TagOption[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
}

export interface QuestionFilters {
    q?: string;
    subject_id?: string;
    topic_id?: string;
    tag?: string;
    status?: string;
    difficulty?: string;
}

export interface ChoiceInput {
    id?: number;
    body: string;
    is_correct: boolean;
}

export interface QuestionDetail {
    id: number;
    subject_id: number;
    topic_id: number;
    body: string;
    explanation: string | null;
    difficulty: Difficulty;
    status: QuestionStatus;
    locked: boolean;
    tags: string[];
    choices: ChoiceInput[];
}
