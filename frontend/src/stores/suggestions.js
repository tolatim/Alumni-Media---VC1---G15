import { defineStore } from "pinia";
import api from "@/services/api";

const SUGGESTIONS_PER_PAGE = 6;

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

export const useSuggestionsStore = defineStore("suggestions", {
    state: () => ({
        suggestions: [],
        suggestionsPagination: defaultPagination(SUGGESTIONS_PER_PAGE),
    }),

    actions: {
        async loadSuggestions(page = 1) {
            const response = await api.get("/users/suggestions", {
                    params: { page, per_page: SUGGESTIONS_PER_PAGE },
                });

                this.suggestions = response.data?.data || [];
                this.suggestionsPagination = normalizePagination(
                    response.data,
                    SUGGESTIONS_PER_PAGE
                );
        },
    },
});