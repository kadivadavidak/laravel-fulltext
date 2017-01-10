<?php
namespace Swis\LaravelFulltext;

class Search implements SearchInterface
{
    /**
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection|\Swis\LaravelFulltext\IndexedRecord[]
     */
    public function run($search){
        $query = $this->searchQuery($search);
        return $query->get();
    }

    /**
     * @param $search
     * @param $class
     * @return \Illuminate\Database\Eloquent\Collection|\Swis\LaravelFulltext\IndexedRecord[]
     */
    public function runForClass($search, $class){
        $query = $this->searchQuery($search);
        $query->where('indexable_type', $class);
        return $query->get();
    }

    /**
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchQuery($search) {
        $terms = TermBuilder::terms($search);

        $termsMatch = ''.$terms->implode(' ');
        $termsBool = '+'.$terms->implode(' +');

        $pdo = \Swis\LaravelFulltext\IndexedRecord::getConnectionResolver()->connection()->getPdo();
        $query = \Swis\LaravelFulltext\IndexedRecord::query()
          ->whereRaw('MATCH (indexed_title, indexed_content) AGAINST (' . $pdo->quote($termsBool) . ' IN BOOLEAN MODE)')
          ->orderByRaw(
            '(' . (float) config('laravel-fulltext.weight.title', 1.5)   . ' * (MATCH (indexed_title) AGAINST (' . $pdo->quote($termsMatch) . ')) +
              ' . (float) config('laravel-fulltext.weight.content', 1.0) . ' * (MATCH (indexed_title, indexed_content) AGAINST (' . $pdo->quote($termsMatch) . '))
             ) DESC')
          ->limit(config('laravel-fulltext.limit-results'))
          ->with('indexable');

        return $query;
    }
}
