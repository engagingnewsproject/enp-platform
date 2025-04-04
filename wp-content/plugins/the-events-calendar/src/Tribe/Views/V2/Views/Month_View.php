<?php
/**
 * The Month View.
 *
 * @since   4.9.3
 * @package Tribe\Events\Views\V2\Views
 */

namespace Tribe\Events\Views\V2\Views;

use Tribe\Events\Views\V2\Messages;
use Tribe\Events\Views\V2\Views\Traits\With_Fast_Forward_Link;
use Tribe\Utils\Query;
use Tribe__Context as Context;
use Tribe__Date_Utils as Dates;
use Tribe__Utils__Array as Arr;
use Tribe\Events\Views\V2\Views\Traits\With_Noindex;

use DateTime;

class Month_View extends By_Day_View {
	use With_Fast_Forward_Link;
	use With_Noindex;

	/**
	 * The default number of events to show per-day.
	 *
	 * @since 4.9.7
	 *
	 * @var int
	 */
	protected static $posts_per_page_default = 12;

	/**
	 * Slug for this view.
	 *
	 * @since 4.9.3
	 * @deprecated 6.0.7
	 *
	 * @var string
	 */
	protected $slug = 'month';

	/**
	 * Statically accessible slug for this view.
	 *
	 * @since 6.0.7
	 *
	 * @var string
	 */
	protected static $view_slug = 'month';

	/**
	 * Cached dates for the prev/next links.
	 *
	 * @since 5.16.1
	 *
	 * @var array
	 */
	protected array $memoized_dates = [];

	/**
	 * Visibility for this view.
	 *
	 * @since 4.9.4
	 * @since 4.9.11 Made the property static.
	 *
	 * @var bool
	 */
	protected static $publicly_visible = true;

	/**
	 * A instance cache property to store the currently fetched grid days.
	 *
	 * @since 4.9.11
	 *
	 * @var array
	 */
	protected $grid_days = [];

	/**
	 * Default untranslated value for the label of this view.
	 *
	 * @since 6.0.4
	 *
	 * @var string
	 */
	protected static $label = 'Month';

	/**
	 * @inheritDoc
	 */
	public static function get_view_label(): string {
		static::$label = _x( 'Month', 'The text label for the Month View.', 'the-events-calendar' );

		return static::filter_view_label( static::$label );
	}

	/**
	 * Get the date of the event immediately previous to the current view date.
	 *
	 * @since 5.16.1
	 *
	 * @param DateTime $current_date A DateTime object signifying the current date for the view.
	 *
	 * @return DateTime|false Either the previous event chronologically, the previous month, or false if no next event found.
	 */
	public function get_previous_event_date( $current_date ) {
		$args = $this->filter_repository_args( parent::setup_repository_args( $this->context ) );

		// Use cache to reduce the performance impact.
		$cache_key = __METHOD__ . '_' . substr( md5( wp_json_encode( [ $current_date, $args ] ) ), 10 );

		if ( isset( $this->memoized_dates[ $cache_key ] ) ) {
			return $this->memoized_dates[ $cache_key ];
		}

		// When dealing with previous event date we only fetch one.
		$args['posts_per_page'] = 1;

		// Find the first event that starts before the start of this month.
		$prev_event = tribe_events()
			->by_args( $args )
			->where( 'starts_before', tribe_beginning_of_day( $current_date->format( 'Y-m-01' ) ) )
			->order( 'DESC' )
			->first();

		if ( ! $prev_event instanceof \WP_Post ) {
			$this->memoized_dates[ $cache_key ] = false;

			return false;
		}

		// Show the closest date on which that event appears (but not the current date).
		$prev_date  = min(
			Dates::build_date_object( $prev_event->dates->start ),
			$current_date->modify( '-1 month' )
		);

		$this->memoized_dates[ $cache_key ] = $prev_date;

		return $prev_date;
	}

	/**
	 * {@inheritDoc}
	 */
	public function prev_url( $canonical = false, array $passthru_vars = [] ) {
		$cache_key = __METHOD__ . '_' . md5( wp_json_encode( func_get_args() ) );

		if ( isset( $this->cached_urls[ $cache_key ] ) ) {
			return $this->cached_urls[ $cache_key ];
		}

		// Setup the Default date for the month view here.
		$default_date = 'today';
		$date         = $this->context->get( 'event_date', $default_date );
		$current_date = Dates::build_date_object( $date );

		if ( $this->skip_empty() ) {
			// Show the closest date on which that event appears (but not the current date).
			$prev_date = $this->get_previous_event_date( $current_date, $canonical );
			if ( ! $prev_date ) {
				return $this->filter_prev_url( $canonical, '' );
			}
		} else {
			$prev_date = Dates::build_date_object( $current_date->format( 'Y-m-01' ) );
			$prev_date->sub( new \DateInterval( 'P1M' ) );
			// Let's make sure to prevent users from paginating endlessly back when we know there are no more events.
			$earliest = $this->context->get( 'earliest_event_date', $prev_date );
			if ( $current_date->format( 'Y-m' ) === Dates::build_date_object( $earliest )->format( 'Y-m' ) ) {
				return $this->filter_prev_url( $canonical, '' );
			}
		}

		$url = $this->build_url_for_date( $prev_date, $canonical, $passthru_vars );
		$url = $this->filter_prev_url( $canonical, $url );

		$this->cached_urls[ $cache_key ] = $url;

		return $url;
	}

	/**
	 * Get the date of the event immediately after to the current view date.
	 *
	 * @since 5.16.1
	 *
	 * @param DateTime|false $current_date A DateTime object signifying the current date for the view.
	 *
	 * @return DateTime|false Either the next event chronologically, the next month, or false if no next event found.
	 */
	public function get_next_event_date( $current_date ) {
		$args = $this->filter_repository_args( parent::setup_repository_args( $this->context ) );

		// Use cache to reduce the performance impact.
		$cache_key = __METHOD__ . '_' . substr( md5( wp_json_encode( [ $current_date, $args ] ) ), 10 );

		if ( isset( $this->memoized_dates[ $cache_key ] ) ) {
			return $this->memoized_dates[ $cache_key ];
		}

		if ( isset( $args['past'] ) && tribe_is_truthy( $args['past'] ) ) {
			return false;
		}

		// For the next event date we only care about 1 item.
		$args['posts_per_page'] = 1;

		// The first event that ends after the end of the month; it could still begin in this month.
		$next_event = tribe_events()
			->by_args( $args )
			->where( 'starts_after', tribe_end_of_day( $current_date->format( 'Y-m-t' ) ) )
			->order( 'ASC' )
			->first();

		if ( ! $next_event instanceof \WP_Post ) {
			$this->memoized_dates[ $cache_key ] = false;

			return false;
		}

		// At a minimum pick the next month or the month the next event starts in.
		$next_date       = max(
			Dates::build_date_object( $next_event->dates->start ),
			$current_date->modify( '+1 month' )
		);

		$this->memoized_dates[ $cache_key ] = $next_date;

		return $next_date;
	}

	/**
	 * {@inheritDoc}
	 */
	public function next_url( $canonical = false, array $passthru_vars = [] ) {
		$cache_key = __METHOD__ . '_' . md5( wp_json_encode( func_get_args() ) );

		if ( isset( $this->cached_urls[ $cache_key ] ) ) {
			return $this->cached_urls[ $cache_key ];
		}

		// Setup the Default date for the month view here.
		$default_date = 'today';
		$date         = $this->context->get( 'event_date', $default_date );
		$current_date = Dates::build_date_object( $date );

		if ( $this->skip_empty() && ! $this->context->get( 'past', false ) ) {
			// At a minimum pick the next month or the month the next event starts in.
			$next_date = $this->get_next_event_date( $current_date, $canonical );
			if ( ! $next_date ) {
				return $this->filter_next_url( $canonical, '' );
			}
		} else {
			$next_date = Dates::build_date_object( $current_date->format( 'Y-m-01' ) );
			$next_date->add( new \DateInterval( 'P1M' ) );
			// Let's make sure to prevent users from paginating endlessly forward when we know there are no more events.
			$latest = $this->context->get( 'latest_event_date', $next_date );
			if ( $current_date->format( 'Y-m' ) === Dates::build_date_object( $latest )->format( 'Y-m' ) ) {
				return $this->filter_next_url( $canonical, '' );
			}
		}

		$url = $this->build_url_for_date( $next_date, $canonical, $passthru_vars );
		$url = $this->filter_next_url( $canonical, $url );

		$this->cached_urls[ $cache_key ] = $url;

		return $url;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function setup_repository_args( Context $context = null ) {
		// Let's apply the arguments common to all Views.
		$args = parent::setup_repository_args( $context );

		$context = null !== $context ? $context : $this->context;

		// Let's override the ones the Month View will use differently.
		// The setting governing the Events > Settings > Display > "Month view events per day" setting.
		$args['posts_per_page'] = $context->get( 'month_posts_per_page', static::$posts_per_page_default );
		// Per-day events never paginate.
		unset( $args['paged'] );

		$date = $context->get( 'event_date', 'now' );

		$this->user_date = Dates::build_date_object( $date )->format( 'Y-m' );

		$args['order_by'] = [
			'menu_order' => 'ASC',
			'event_date' => 'ASC',
		];
		$args['order']    = 'ASC';

		return $args;
	}

	/**
	 * Overrides the base implementation to use the Month view custom number of events per day.
	 *
	 * @since 4.9.7
	 *
	 * @return int The Month view number of events per day.
	 */
	protected function get_events_per_day() {
		$events_per_day = $this->context->get( 'month_posts_per_page', 12 );

		/**
		 * Filters the number of events per day to fetch in the Month view.
		 *
		 * @since 4.9.7
		 *
		 * @param int $events_per_day The default number of events that will be fetched for each day.
		 * @param Month_View $this The current Month View instance.
		 */
		return apply_filters( 'tribe_events_views_v2_month_events_per_day', $events_per_day, $this );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function setup_template_vars() {
		// The events will be returned in an array with shape `[ <Y-m-d> => [...<events>], <Y-m-d> => [...<events>] ]`.
		$grid_days = $this->get_grid_days();
		// Set this to be used in the following methods.
		$this->grid_days = $grid_days;

		$grid_start_date = array_keys( $grid_days );
		$grid_start_date = reset( $grid_start_date );

		/*
		 * We'll run the fetches day-by-day, we do not want to run a potentially expensive query so we pre-fill the
		 * repository query with results we already have.
		 * We replace the repository for the benefit of the parent method, and then restore it.
		 */
		$original_repository = $this->repository;
		$this->repository = tribe_events();
		$all_month_events = array_unique( array_merge( ...array_values( $grid_days ) ) );
		$this->repository->set_query( Query::for_posts( $all_month_events ) );

		$template_vars = parent::setup_template_vars();

		$this->repository = $original_repository;

		$days = $this->get_days_data( $grid_days );

		$grid_date_str                 = $this->context->get( 'event_date', 'today' );
		$grid_date                     = Dates::build_date_object( $grid_date_str );
		$month_and_year_format         = tribe_get_option( 'monthAndYearFormat', 'F Y' );
		$month_and_year_format_compact = Dates::datepicker_formats( tribe_get_option( 'datepickerFormat', 'm1' ) );

		$next_month_num = Dates::build_date_object( $grid_date_str )->modify( 'first day of next month' )->format( 'n' );
		$prev_month_num = Dates::build_date_object( $grid_date_str )->modify( 'first day of last month' )->format( 'n' );
		$next_month     = Dates::wp_locale_month( $next_month_num, 'short' );
		$prev_month     = Dates::wp_locale_month( $prev_month_num, 'short' );
		$index_next_rel = true;
		$index_prev_rel = true;

		if ( ! $this->skip_empty() ) {
			$next_event_date = $this->get_next_event_date( Dates::build_date_object( $grid_date_str ) );
			$previous_event_date = $this->get_previous_event_date( Dates::build_date_object( $grid_date_str ) );

			$index_next_rel = $next_event_date && $next_month_num === $next_event_date->format( 'n' );
			$index_prev_rel = $previous_event_date && $prev_month_num === $previous_event_date->format( 'n' );
		}

		$next_rel = $index_next_rel ? 'next' : 'noindex';
		$prev_rel = $index_prev_rel ? 'prev' : 'noindex';

		$mobile_messages = $this->get_mobile_messages();

		$today                                       = $this->context->get( 'today' );
		$template_vars['the_date']                   = $grid_date;
		$template_vars['today_date']                 = Dates::build_date_object( $today )->format( 'Y-m-d' );
		$template_vars['grid_date']                  = $grid_date->format( 'Y-m-d' );
		$template_vars['formatted_grid_date']        = $grid_date->format_i18n( $month_and_year_format );
		$template_vars['formatted_grid_date_mobile'] = $grid_date->format( $month_and_year_format_compact );
		$template_vars['events']                     = $grid_days;
		$template_vars['days']                       = $days;
		$template_vars['next_month']                 = $next_month_num;
		$template_vars['prev_month']                 = $prev_month_num;
		$template_vars['next_rel']                   = $next_rel;
		$template_vars['prev_rel']                   = $prev_rel;
		$template_vars['next_label']                 = $next_month;
		$template_vars['prev_label']                 = $prev_month;
		$template_vars['messages']                   = $this->messages->to_array();
		$template_vars['mobile_messages']            = $mobile_messages;
		$template_vars['grid_start_date']            = $grid_start_date;

		return $template_vars;
	}

	/**
	 * Parses the multi-day events and produces the multi-day "stack", including spacers.
	 *
	 * @since 4.9.7
	 *
	 * @param array $grid_events_by_day An array of events, per-day, in the shape `[ <Y-m-d> => [...<event_ids> ] ]`;
	 *
	 * @return array An array of all the month days, each entry filled with spacers and/or event post IDs in the correct
	 *               order. E.g.
	 *               `[ '2019-07-01' => [2, 3, false], '2019-07-02' => [2, 3, 4], '2019-07-03' => [false, 3, 4]]`.
	 */
	protected function build_day_stacks( array $grid_events_by_day ) {
		$week_stacks = [];
		foreach ( array_chunk( $grid_events_by_day, 7, true ) as $week_events_by_day ) {
			$week_stacks[] = $this->stack->build_from_events( $week_events_by_day );
		}

		return array_merge( ...$week_stacks );
	}

	/**
	 * Populates the data for each day in the grid and returns it.
	 *
	 * @since 4.9.7
	 *
	 * @param array $grid_days An associative array of events per day, in the shape `[ <Y-m-d> => [...<events>] ]`.
	 *
	 * @return array An associative array of day data for each day in the shape `[ <Y-m-d> => <day_data> ]`.
	 */
	protected function get_days_data( array $grid_days ) {
		$found_events = $this->get_grid_days_counts();
		$events_per_day = $this->get_events_per_day();

		// The multi-day stack will contain spacers and post IDs.
		$day_stacks = $this->build_day_stacks( $grid_days );

		// Let's prepare an array of days more digestible by the templates.
		$days = [];

		$default_day_url_args = array_merge( $this->get_url_args(), [ 'eventDisplay' => Day_View::get_view_slug() ] );

		/**
		 * Allows filtering the base URL arguments that will be added to each "View More" link in Month View.
		 *
		 * The URL arguments will be used to build each day "View More" link URL and, while building each day URL,
		 * the day date will be merged with these default arguments.
		 *
		 * @since 5.3.0
		 *
		 * @param array<string,string|int|float> $default_day_url_args A default set of URL arguments that will be used to build
		 *                                                             the View More link.
		 */
		$default_day_url_args = apply_filters( 'tribe_events_views_v2_month_view_more_url_args', $default_day_url_args, $this );

		foreach ( $grid_days as $day_date => $day_events ) {
			/**
			 * This will be used to call `tribe_get_event` in the context of a specific week for each day
			 * to have valid and coherent `starts_this_week` and `ends_this_week` properties set.
			 *
			 * @see tribe_get_event()
			 */
			$date_object = Dates::build_date_object( $day_date );

			// The multi-day stack includes spacers; that's why we use `element`.
			$day_stack = array_map( static function ( $element ) use ( $date_object ) {
				// If it's numeric make an event object of it.
				return is_numeric( $element ) ?
					tribe_get_event( $element, OBJECT, $date_object->format( 'Y-m-d' ) )
					: $element;
			}, Arr::get( $day_stacks, $day_date, [] ) );

			// All non-multiday events.
			$the_day_events = array_map( 'tribe_get_event',
				array_filter( $day_events, static function ( $event ) use ( $date_object ) {
					$event = tribe_get_event( $event, OBJECT, $date_object->format( 'Y-m-d' ) );

					return $event instanceof \WP_Post && ! ( $event->multiday > 1 || $event->all_day );
				} )
			);

			/**
			 * This is used for determining if a day has featured events - ex: for the mobile icon.
			 * The events themselves are not used in the template, yet.
			 */
			$featured_events = array_map( 'tribe_get_event',
				array_filter( $day_events,
					static function ( $event ) use ( $date_object ) {
						$event = tribe_get_event( $event, OBJECT, $date_object->format( 'Y-m-d' ) );

						return $event instanceof \WP_Post && $event->featured;
					} )
			);

			usort(
				$the_day_events,
				function ( $event_a, $event_b )  {
					$a = [
						(int) ( -1 === $event_a->menu_order ),
						( (int) ( -1 === $event_a->menu_order ) && (int) $event_a->featured  )
					];

					$b = [
						(int) ( -1 === $event_b->menu_order ),
						( (int) ( -1 === $event_b->menu_order ) && (int) $event_b->featured  )
					];

					if ( $b > $a ) {
						return 1;
					}

					if ( $b < $a ) {
						return -1;
					}

					return 0;
				}
			);

			if ( $events_per_day > -1 ) {
				$the_day_events = array_slice( array_filter( $the_day_events ), 0, $events_per_day );
			}

			$more_events      = 0;
			$day_found_events = Arr::get( $found_events, $day_date, 0 );

			if ( $day_found_events ) {
				/*
				 * We cannot know before-hand what spacer will be used (it's filterable) so we have to count the events
				 * by keeping only the posts.
				 */
				$stack_events_count = count(
					array_filter(
						$day_stack,
						static function ( $el ) {
							return $el instanceof \WP_Post;
						}
					)
				);

				/*
				 * In the context of the Month View we want to know if there are more events we're not going to see.
				 * So we exclude the ones we'll see and the multi-day ones in the multi-day stack.
				 */
				$more_events = max( 0, $day_found_events - $stack_events_count - count( $the_day_events ) );
			}

			$day_url_args     = array_merge( $default_day_url_args, [ 'eventDate' => $day_date ] );
			$day_data         = [
				'date'             => $day_date,
				'is_start_of_week' => (int) get_option( 'start_of_week', 0 ) === (int) $date_object->format( 'w' ),
				'year_number'      => $date_object->format( 'Y' ),
				'month_number'     => $date_object->format( 'm' ),
				'day_number'       => $date_object->format( 'j' ),
				'events'           => $the_day_events,
				'featured_events'  => $featured_events,
				'multiday_events'  => $day_stack,
				'found_events'     => $day_found_events,
				'more_events'      => $more_events,
				'day_url'          => tribe_events_get_url( $day_url_args ),
			];

			$days[ $day_date ] = $day_data;
		}

		return $days;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function calculate_grid_start_end( $date ) {
		$grid_start = static::calculate_first_cell_date( $date );
		$grid_end   = static::calculate_final_cell_date( $date );

		return [ Dates::build_date_object( $grid_start ), Dates::build_date_object( $grid_end ) ];
	}

	/**
	 * Return the date of the first day in the month view grid.
	 *
	 * This is not necessarily the 1st of the specified month, rather it is the date of the
	 * first grid cell which could be anything upto 6 days earlier than the 1st of the month.
	 *
	 * @since 6.0.0
	 *
	 * @param string  $month
	 * @param integer $start_of_week
	 *
	 * @return bool|string (Y-m-d)
	 */
	public static function calculate_first_cell_date( $month, $start_of_week = null ) {
		if ( null === $start_of_week ) {
			$start_of_week = (int) get_option( 'start_of_week', 0 );
		}

		$day_1 = Dates::first_day_in_month( $month );
		if ( $day_1 < $start_of_week ) {
			$day_1 += 7;
		}

		$diff = $day_1 - $start_of_week;
		if ( $diff >= 0 ) {
			$diff = "-$diff";
		}

		try {
			$date = new \DateTime( $month );
			$date = new \DateTime( $date->format( 'Y-m-01' ) );
			$date->modify( "$diff days" );

			return $date->format( Dates::DBDATEFORMAT );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Return the date of the first day in the month view grid.
	 *
	 * This is not necessarily the last day of the specified month, rather it is the date of
	 * the final grid cell which could be anything upto 6 days into the next month.
	 *
	 * @since 6.0.0
	 *
	 * @param string  $month
	 * @param integer $start_of_week
	 *
	 * @return bool|string (Y-m-d)
	 */
	public static function calculate_final_cell_date( $month, $start_of_week = null ) {
		if ( null === $start_of_week ) {
			$start_of_week = (int) get_option( 'start_of_week', 0 );
		}

		$last_day    = Dates::last_day_in_month( $month );
		$end_of_week = Dates::week_ends_on( $start_of_week );
		if ( $end_of_week < $last_day ) {
			$end_of_week += 7;
		}

		$diff = $end_of_week - $last_day;
		if ( $diff >= 0 ) {
			$diff = "+$diff";
		}

		try {
			$date = new \DateTime( $month );
			$date = new \DateTime( $date->format( 'Y-m-t' ) );
			$date->modify( "$diff days" );

			return $date->format( Dates::DBDATEFORMAT );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 4.9.9
	 */
	protected function get_label_format() {
		// Something like "January".
		return 'F';
	}

	/**
	 * Whether months w/o any event should be skipped while building navigation links or not.
	 *
	 * By default empty months will not be skipped.
	 *
	 * @since 4.9.9
	 *
	 * @return bool Whether to skip empty months or not.
	 */
	protected function skip_empty() {
		/**
		 * Filters whether months w/o any event should be skipped while building navigation links or not.
		 *
		 * @since 4.9.9
		 *
		 * @param bool       $skip_empty   Whether months w/o any event should be skipped while building
		 *                                 navigation links or not; defaults to `false`.
		 * @param Month_View $this         This Month View instance.
		 */
		return (bool) apply_filters( 'tribe_events_views_v2_month_nav_skip_empty', false, $this );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_url_date_format() {
		return 'Y-m';
	}

	/**
	 * Overrides the base method to handle messages specific to the Month View.
	 *
	 * @since 4.9.11
	 *
	 * @param array $events An array of events found on the Month.
	 */
	protected function setup_messages( array $events ) {
		if ( ! empty( $events )
		     || (
			     ! empty( $this->grid_days )
			     && 0 !== array_sum( array_map( 'count', $this->grid_days ) )
		     )
		) {
			return;
		}

		$keyword = $this->context->get( 'keyword', false );

		if ( $keyword ) {
			$this->messages->insert(
				Messages::TYPE_NOTICE,
				Messages::for_key( 'month_no_results_found_w_keyword', esc_html( trim( $keyword ) ) )
			);

			return;
		}

		$fast_forward_link = $this->get_fast_forward_link( true );

		if ( ! empty( $fast_forward_link ) ) {
			$this->messages->insert(
				Messages::TYPE_NOTICE,
				Messages::for_key( 'month_no_results_found_w_ff_link', $fast_forward_link ),
				9
			);

			return;
		}

		$message_key = $this->upcoming_events_count() ? 'no_results_found' : 'no_upcoming_events';
		$this->messages->insert(
			Messages::TYPE_NOTICE,
			Messages::for_key( $message_key ),
			9
		);
	}

	/**
	 * Overrides the base View implementation to limit the results to the View grid.
	 *
	 * {@inheritdoc}
	 */
	protected function setup_ical_repository_args( $per_page ) {
		if ( empty( $this->repository_args ) ) {
			$this->repository->by_args( $this->get_repository_args() );
		}

		$this->repository->per_page( $per_page );

		$now = Dates::build_date_object( 'now' );
		$context_date = $this->context->get( 'event_date', $now->format( 'Y-m-d') );
		$event_date   = Dates::build_date_object( $context_date );

		/**
		 * Allows filtering between starting the ics export on the 1st of the month or the current day.
		 *
		 * @since 5.14.1
		 *
		 * @param bool $start_today Whether to start from the current day (true) or the first of the month (false).
		 */
		$start_today = apply_filters( 'tribe_events_views_v2_month_ics_start_today', true );

		// If we're dealing with the current year & month, use the current day, otherwise use the 1st.
		// Affected by the filter above. If it returns `false` we'll go straight to 'Y-m-01' (the first).
		$start_format = $start_today && $now->format( 'Y-m') === $event_date->format( 'Y-m' ) ? 'Y-m-d' : 'Y-m-01';

		$start_date   = tribe_beginning_of_day( $event_date->format( $start_format ) );
		$end_date     = tribe_end_of_day( $event_date->format( 'Y-m-t' ) );

		$this->repository->where( 'ends_after', $start_date );
		$this->repository->where( 'starts_before', $end_date );
	}

	/**
	 * Returns a set of messages that will be show to the user in the mobile interaction.
	 *
	 * @since 5.7.0
	 *
	 * @return array<string,array<string|int,string>> A map from message types to messages for
	 *                                                each type.
	 */
	protected function get_mobile_messages() {
		$mobile_messages = [
			'notice' =>
				[
					'no-events-in-day' => _x(
						'There are no events on this day.',
						'A message shown in the mobile version when a day without events is selected.',
						'the-events-calendar'
					)
				]
		];

		/**
		 * Allows filtering the mobile messages Month view will display to the user depending on the interaction.
		 *
		 * @since 5.7.0
		 *
		 * @param array<string,array<string|int,string>> A map from message types to messages for
		 *                                                each type.
		 * @param Month_View $view                        A reference to the View instance that is filtering its mobile messages.
		 */
		return apply_filters( 'tribe_events_views_v2_month_mobile_messages', $mobile_messages, $this );
	}
}
