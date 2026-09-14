<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtReview;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select(['id', 'username', 'email', 'is_admin', 'created_at'])
            ->withCount(['courts', 'reviews'])
            ->latest()
            ->get();

        $courts = Court::query()
            ->select(['id', 'name', 'address', 'city', 'state', 'description', 'rating', 'created_at', 'user_id'])
            ->with(['user:id,username'])
            ->withCount('reviews')
            ->latest()
            ->get();

        $courtReviews = CourtReview::query()
            ->select(['id', 'court_id', 'user_id', 'username', 'rating', 'comment', 'created_at'])
            ->with(['court:id,name', 'user:id,username'])
            ->latest()
            ->get();

        return view('admin.dashboard', compact('users', 'courts', 'courtReviews'));
    }
}
