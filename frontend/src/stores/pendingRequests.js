import { defineStore } from "pinia";
import api from "@/services/api";

const PENDING_PER_PAGE = 6;

const defaultPagination = (perPage) => ({
    current_page: 1,
    last_page: 1,
    per_page: perPage,
    total: 0,
});

const normalizePagination = (payload, fallbackPerPage) => {
    const pagination = payload?.pagination;
    if (pagination) return pagination;

    const list = Array.isArray(payload?.data) ? payload.data : [];
    return {
        current_page: 1,
        last_page: 1,
        per_page: fallbackPerPage,
        total: list.length,
    };
};

export const usePendingRequestsStore = defineStore("pendingRequests", {
    state: () => ({
        pendingRequests: [],
        pendingPagination: defaultPagination(PENDING_PER_PAGE),
    }),

    actions: {
        async loadPendingRequests(page = 1) {
            const response = await api.get("/connections/pending", {
                params: { page, per_page: PENDING_PER_PAGE },
            });

            this.pendingRequests = response.data?.data || [];
            this.pendingPagination = normalizePagination(
                response.data,
                PENDING_PER_PAGE
            );
        },

        addPendingRequest(request) {
            this.pendingRequests.unshift(request);
        },
    },
});