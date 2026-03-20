import { defineStore } from "pinia";
import api from "@/services/api";

const CONNECTIONS_PER_PAGE = 8;

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

export const useConnectionsStore = defineStore('connectionRows', {
    state: () => ({
        connectionRows: [],
        friendsPagination: defaultPagination(CONNECTIONS_PER_PAGE)
    }),

    actions:{
        async loadMyConnections(page = 1){
            const response = await api.get('/connections/my',{
                params: {page, per_page: CONNECTIONS_PER_PAGE},
            })
            this.connectionRows = response.data?.data || [];
            this.friendsPagination = normalizePagination(
                response.data,
                CONNECTIONS_PER_PAGE
            );
        }
    }
})