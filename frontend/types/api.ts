export type ApplicantAttributesData = {
name: string,
email: string,
aiScore: number | null,
tier: ScoreTier,
status: ApplicantStatus,
appliedAt: string,
highlights: string[],
};
export type ApplicantDocumentData = {
data: ApplicantResourceData,
};
export type ApplicantResourceData = {
type: string,
id: string,
attributes: ApplicantAttributesData,
};
export type ApplicantStatus = 'pending' | 'shortlisted' | 'rejected';
export type ApplicantSummaryData = {
totalApplicants: number,
eligibleApplicants: number,
averageAiScore: number,
topTier: TopTierApplicantData[],
};
export type ApplicantSummaryDocumentData = {
data: ApplicantResourceData[],
meta: ApplicantSummaryMetaData,
links: PaginationLinksData,
};
export type ApplicantSummaryMetaData = {
summary: ApplicantSummaryData,
page: PageMetaData,
};
export type CursorPaginatedDataCollection<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type JsonApiErrorData = {
status: string,
title: string,
detail?: string,
code?: string,
source?: JsonApiErrorSourceData,
};
export type JsonApiErrorDocumentData = {
errors: JsonApiErrorData[],
};
export type JsonApiErrorSourceData = {
parameter?: string,
pointer?: string,
};
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type PageMetaData = {
currentPage: number,
perPage: number,
total: number,
lastPage: number,
};
export type PaginatedDataCollection<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type PaginationLinksData = {
self: string,
first: string,
prev: string | null,
next: string | null,
last: string,
};
export type ScoreTier = 'top' | 'eligible' | 'below' | 'unscored';
export type TopTierApplicantData = {
id: string,
name: string,
aiScore: number,
};
